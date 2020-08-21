<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\PedidoProduto;
use Wms\Domain\Entity\Expedicao;
use Wms\Math;

class PedidoProdutoRepository extends EntityRepository
{

        public function cortaItem($codPedido,$codProduto,$grade,$qtd,$motivo, $motivoEn = null) {

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
            $mapaSeparacaoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');

            /** @var \Wms\Domain\Entity\Expedicao\PedidoProduto $ppEn */

            $ppEn = $this->findOneBy(array('codPedido'=>$codPedido,
                                           'codProduto'=>$codProduto,
                                           'grade'=>$grade));
            $expedicaoEn = $ppEn->getPedido()->getCarga()->getExpedicao();
            $idExpedicao = $expedicaoEn->getId();

            /* Verificar se pode cortar o pedido */
            $qtdCortada = $ppEn->getQtdCortada();
            $qtdPedido = $ppEn->getQuantidade();
            $corteTotal = Math::adicionar($qtdCortada, $qtd);

            if (Math::compare($corteTotal, $qtdPedido, ">")) {
                throw new \Exception("A quantidade já cortada de $qtdCortada mais a solicitação atual de ($qtd) excede o saldo do pedido de $qtdPedido");
            }

            if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_FINALIZADO) {
                throw new \Exception('Não pode ter novos cortes em expedição finalizada!');
            }
            $idUsuario = $this->getSystemParameterValue('ID_USER_ERP');

            $andamentoRepo->save("Corte de $qtd qtd do produto $codProduto, grade $grade no pedido $codPedido - $motivo", $idExpedicao, $idUsuario, false, null,null, false);

            /* CORTA AS ETIQUETAS */
            $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $etiquetasEn = $etiquetaRepo->findBy(array('codProduto'=>$codProduto,
                                                       'dscGrade'=>$grade,
                                                       'pedido'=>$codPedido),
                                                 array('codStatus' => 'ASC',
                                                       'qtdEmbalagem'=>'DESC'));

            $qtdPendente = $qtd;
            foreach ($etiquetasEn as $etiquetaEn){

                if ($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_PENDENTE_CORTE) throw new \Exception('Existem etiquetas pendentes de corte nesta expedição! Corte-as primeiro antes de efetuar um novo corte');

                if ($qtdPendente <=0) continue;

                /*
                 * Com esta validação, só vai deduzir da quantidade pendente se estiver passando por uma etiqueta que seja:
                 *  -> Etiqueta de Produto Unitário
                 *  -> Etiqueta de Produto Volume que esteja sendo usado como referencia por todos os volumes
                 *
                 * Assim o sistema evita que a quantidade pendente seja incrementada para cada volume do produto
                 */
                if ($etiquetaEn->getProdutoVolume() != null AND $etiquetaEn->getCodReferencia() != null) continue;

                if ($etiquetaEn->getQtdEmbalagem() > $qtdPendente) continue;

                if ($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_CORTADO) continue;

                $qtdEmbVol = $etiquetaEn->getQtdEmbalagem();

                $arrEtiquetas = array();
                $arrEtiquetas[] = $etiquetaEn;

                /*
                 * Caso seja a etiqueta principal do grupo do volume, encontra todas as etiquetas vinculadas
                 *  para que todas sejam colocadas como pendente de corte
                 */
                if ($etiquetaEn->getProdutoVolume() != null AND $etiquetaEn->getCodReferencia() == null) {
                    $etiquetas = $etiquetaRepo->findBy(array('codReferencia' => $etiquetaEn->getId()));
                    foreach ($etiquetas as $etqEn) {
                        $arrEtiquetas[] = $etqEn;
                    }
                }

                foreach ($arrEtiquetas as $etqEn) {

                    if ($etqEn->getCodStatus() == EtiquetaSeparacao::STATUS_CORTADO) continue;

                    $etiquetaRepo->alteraStatus($etqEn,EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                    $codBarrasEtiqueta = $etqEn->getId();
                    if ($etqEn->getProdutoEmbalagem() != NULL) {
                        $codBarrasProdutos = $etqEn->getProdutoEmbalagem()->getCodigoBarras();
                    } else {
                        $codBarrasProdutos = $etqEn->getProdutoVolume()->getCodigoBarras();
                    }

                    $andamentoRepo->save("Etiqueta $codBarrasEtiqueta colocada em Pendencia de Corte via integração", $idExpedicao, $idUsuario, false, $codBarrasEtiqueta, $codBarrasProdutos, false);
                }

                $qtdPendente = $qtdPendente - $qtdEmbVol;
            }

            /* CORTA OS MAPAS */
            if ($qtdPendente >0) {

                /* CORTA O PEDIDO APENAS A QUANTIDADE QUE NÃO TEM ETIQUETA GERADA, POIS A ETIQUETA UTILIZA O CONCEITO DE PENDENCIA DE CORTE */
                $ppEn->setQtdCortada($ppEn->getQtdCortada() + $qtdPendente);
                $this->getEntityManager()->persist($ppEn);
                $this->getEntityManager()->flush();

                $corteMapa = array();
                $corteMapa[] = array(
                    'carga' => $ppEn->getPedido()->getCarga()->getId(),
                    'pedido' => $ppEn->getPedido()->getId(),
                    'produto' => $codProduto,
                    'grade' => $grade,
                    'quantidade' => $ppEn->getQuantidade(),
                    'qtdCortada' => $qtd);
                $mapaSeparacaoProdutoRepository->validaCorteMapasERP($corteMapa);
            }

        }

        public function aplicaCortesbyERP($pedidosProdutosWMS, $pedidosProdutosERP) {
            /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepository */
            $pedidoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto');
            $cortes = array();
            foreach ($pedidosProdutosWMS as $produtoWms) {
                $encontrouProdutoERP = false;
                $codProdutoWMS = $produtoWms['produto'];
                $codPedidoWMS = $produtoWms['pedido'];
                $codPedidoExterno = $produtoWms['codPedidoERP'];

                $gradeWMS = $produtoWms['grade'];
                $qtdWms = str_replace(',','.',$produtoWms['quantidade']);
                $qtdCortadaWms= 0;
                if ($produtoWms['qtdCortada'] != null) {
                    $qtdCortadaWms= str_replace(',','.',$produtoWms['qtdCortada']);;
                }
                foreach ($pedidosProdutosERP as $key => $produtoERP) {
                    $codProdutoERP = $produtoERP['PRODUTO'];
                    $codPedidoERP = $produtoERP['PEDIDO'];
                    $gradeERP = $produtoERP['GRADE'];
                    $qtdERP = str_replace(',','.',$produtoERP['QTD']);

                    if (($codProdutoWMS == $codProdutoERP) && ($codPedidoExterno == $codPedidoERP) && ($gradeWMS == $gradeERP)) {
                        if ($qtdERP > $qtdWms) $qtdERP = $qtdWms;
                        $qtdCortar = $qtdWms - $qtdERP;

                        //if ($qtdCortar >0) {
                            $cortes[] = array(
                                'codPedido' => $codPedidoWMS,
                                'codProduto' => $codProdutoWMS,
                                'grade'=>$gradeWMS,
                                'qtdCortar' => $qtdCortar,
                                'tipo' => 'parcial'
                            );
                        //}

                        $encontrouProdutoERP = true;
                        unset($pedidosProdutosERP[$key]);
                        break;
                    }
                }

                if ($encontrouProdutoERP == false) {
                    //if ($qtdCortar >0) {
                        $cortes[] = array(
                            'codPedido' => $codPedidoWMS,
                            'codProduto' => $codProdutoWMS,
                            'grade'=>$gradeWMS,
                            'qtdCortar' => $qtdWms,
                            'tipo' => 'total'
                        );
                    //}
                }
            }

            foreach ($cortes as $corte) {
                $pedidoProdutoEntity = $pedidoProdutoRepository->findOneBy(array(
                    'codPedido' => $corte['codPedido'],
                    'codProduto' => $corte['codProduto'],
                    'grade' => $corte['grade']));
                $qtdCortar = $corte['qtdCortar'];
                if (isset($pedidoProdutoEntity) && !empty($pedidoProdutoEntity)) {
                    $pedidoProdutoEntity->setQtdCortada($qtdCortar);
                    $this->getEntityManager()->persist($pedidoProdutoEntity);
                }
            }

            $this->getEntityManager()->flush();
            return true;
        }


        public function aplicaCortesbyERPOld($pedidosProdutosWMS, $pedidosProdutosERP) {

        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepository */
        $pedidoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto');

        foreach ($pedidosProdutosWMS as $produtoWms) {
            $encontrouProdutoERP = false;
            foreach ($pedidosProdutosERP as $key => $produtoERP) {
                if (in_array(strval($produtoWms['pedido']),$produtoERP)) {
                    if (in_array($produtoWms['produto'],$produtoERP)) {
                        if (in_array($produtoWms['grade'],$produtoERP)) {
                            $pedidoProdutoEntity = $pedidoProdutoRepository->findOneBy(array(
                                'codPedido' => $produtoWms['pedido'],
                                'codProduto' => $produtoWms['produto'],
                                'grade' => $produtoWms['grade']));
                            if (isset($pedidoProdutoEntity) && !empty($pedidoProdutoEntity)) {
                                $encontrouProdutoERP = true;
                                $cortesProduto = array(
                                    'codPedido' => $produtoWms['pedido'],
                                    'codProduto' => $produtoWms['produto'],
                                    'grade' => $produtoWms['grade'],
                                    'quantidadeCortar' => str_replace(',','.',$pedidoProdutoEntity->getQuantidade()) - str_replace(',','.',$produtoERP['QTD']),
                                    'pedidoProduto' => $pedidoProdutoEntity->getId()
                                );
                                if ($cortesProduto['quantidadeCortar'] >= $pedidoProdutoEntity->getQtdCortada()) {
                                    $pedidoProdutoEntity->setQtdCortada($cortesProduto['quantidadeCortar']);
                                    $this->getEntityManager()->persist($pedidoProdutoEntity);
                                }
                                break;
                            }
                        }
                    }
                }
            }

            if (!$encontrouProdutoERP) {
                $pedidoProdutoEntity = $pedidoProdutoRepository->findOneBy(array(
                    'codPedido' => $produtoWms['pedido'],
                    'codProduto' => $produtoWms['produto'],
                    'grade' => $produtoWms['grade']));

                if (isset($pedidoProdutoEntity) && !empty($pedidoProdutoEntity)) {
                    $cortesProduto = array(
                        'codPedido' => $produtoWms['pedido'],
                        'codProduto' => $produtoWms['produto'],
                        'grade' => $produtoWms['grade'],
                        'quantidadeCortar' => $pedidoProdutoEntity->getQuantidade(),
                        'pedidoProduto' => $pedidoProdutoEntity->getId()
                    );
                    $pedidoProdutoEntity->setQtdCortada($cortesProduto['quantidadeCortar']);
                    $this->getEntityManager()->persist($pedidoProdutoEntity);
                }
            }
        }

        $this->getEntityManager()->flush();

        return true;
    }

    public function save($pedido) {

        $em = $this->getEntityManager();

        try {
            $enPedidoProduto = new PedidoProduto;
            \Zend\Stdlib\Configurator::configure($enPedidoProduto, $pedido);
            $em->persist($enPedidoProduto);

        } catch(\Exception $e) {
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enPedidoProduto;
    }

    public function getFilialByProduto($idPedido)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('f.codExterno', 'f.indUtilizaRessuprimento', 'prod.id produto', 'prod.grade', 'ex.id expedicao', 'pp.quantidade')
            ->from('wms:Expedicao\PedidoProduto', 'pp')
            ->innerJoin('pp.pedido', 'p')
            ->innerJoin('wms:Filial', 'f', 'WITH', 'f.codExterno = p.centralEntrega')
            ->innerJoin('pp.produto', 'prod')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'ex')
            ->where("pp.codPedido = $idPedido");

        return $dql->getQuery()->getResult();
    }

    public function identificaExpedicaoPedido($dados)
    {
        $produto = $dados['produto'];
        $grade = $dados['grade'];
        $expedicao = $dados['expedicao'];
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('re.id reservaEstoque')
            ->from('wms:Ressuprimento\ReservaEstoqueProduto', 'rep')
            ->innerJoin('rep.produto', 'p')
            ->innerJoin('rep.reservaEstoque', 're')
            ->innerJoin('wms:Ressuprimento\ReservaEstoqueExpedicao', 'ree', 'WITH', 'ree.reservaEstoque = re.id')
            ->innerJoin('ree.expedicao', 'ex')
            ->where("p.id = $produto AND p.grade = '$grade' AND ex.id = $expedicao")
            ->groupBy('re.id');

        return $dql->getQuery()->getResult();
    }

    public function compareMapaProdutoByPedido($produtoWms)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('(msp.qtdSeparar * msp.qtdEmbalagem) AS corteMaximo, msp.id')
            ->from('wms:Expedicao\MapaSeparacaoProduto','msp')
            ->innerJoin('msp.mapaSeparacao','ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoPedido','msped','WITH','ms.id = msped.mapaSeparacao')
            ->innerJoin('wms:Expedicao\PedidoProduto','pp','WITH','pp.id = msped.pedidoProduto AND pp.codProduto = msp.codProduto AND pp.grade = msp.dscGrade')
            ->where("pp.id = $produtoWms[pedidoProduto]")
            ->andWhere("pp.codProduto = $produtoWms[codProduto]")
            ->andWhere("pp.grade = '$produtoWms[grade]'")
            ->orderBy('msp.qtdSeparar * msp.qtdEmbalagem','ASC');

        return $sql->getQuery()->getResult();
    }

    public function getPedidoProdutoByExpedicao($idExpedicao)
    {
        $sql = "SELECT P.COD_PRODUTO, P.DSC_PRODUTO, P.DSC_GRADE, SUM(DISTINCT QUANTIDADE) QUANTIDADE 
                    FROM PEDIDO_PRODUTO PP 
                    INNER JOIN PRODUTO P ON PP.COD_PRODUTO = P.COD_PRODUTO 
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO 
                    LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO 
                    INNER JOIN PEDIDO P ON PP.COD_PEDIDO = P.COD_PEDIDO 
                WHERE P.COD_PEDIDO IN (
                                          SELECT COD_PEDIDO 
                                            FROM PEDIDO 
                                          WHERE COD_CARGA IN (
                                                SELECT COD_CARGA 
                                                  FROM CARGA 
                                                WHERE COD_EXPEDICAO = $idExpedicao
                                          )
                                      ) 
                    AND (PE.IND_IMPRIMIR_CB = 'S' OR PV.IND_IMPRIMIR_CB = 'S') 
                GROUP BY P.COD_PRODUTO, P.DSC_PRODUTO, P.DSC_GRADE";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

}