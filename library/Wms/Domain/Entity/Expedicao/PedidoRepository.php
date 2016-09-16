<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Zend\Stdlib\Configurator;

class PedidoRepository extends EntityRepository
{

    /**
     * @param $pedido
     * @return Pedido
     * @throws \Exception
     */
    public function save($pedido)
    {

        $em = $this->getEntityManager();

//        $em->beginTransaction();
        try {
            $enPedido = new Pedido;

            $SiglaRepo      = $em->getRepository('wms:Util\Sigla');
            $entitySigla    = $SiglaRepo->findOneBy(array('sigla' => $pedido['tipoPedido']));

            if ($entitySigla == null) {
                throw new \Exception('O tipo de pedido '.$pedido['tipoPedido'].' não esta cadastrado');
            }

            $enPedido->setId($pedido['codPedido']);
            $enPedido->setTipoPedido($entitySigla);
            $enPedido->setLinhaEntrega($pedido['linhaEntrega']);
            $enPedido->setCentralEntrega($pedido['centralEntrega']);
            $enPedido->setCarga($pedido['carga']);
            $enPedido->setItinerario($pedido['itinerario']);
            $enPedido->setPessoa($pedido['pessoa']);
            $enPedido->setPontoTransbordo($pedido['pontoTransbordo']);
            $enPedido->setEnvioParaLoja($pedido['envioParaLoja']);
            $enPedido->setIndEtiquetaMapaGerado('N');
            $em->persist($enPedido);
 //           $em->flush();
 //           $em->commit();

        } catch(\Exception $e) {
 //           $em->rollback();
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enPedido;
    }

    public function getQtdPedidaAtendidaByPedido ($codPedido) {
        $SQL = "SELECT PP.COD_PRODUTO, PP.DSC_GRADE, PP.QUANTIDADE as QTD_PEDIDO, PP.QTD_ATENDIDA
                  FROM PEDIDO_PRODUTO PP
                 WHERE PP.COD_PEDIDO = '$codPedido'";
        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function finalizaPedidosByCentral ($PontoTransbordo, $Expedicao)
    {
        $query = "SELECT ped
                    FROM wms:Expedicao\Pedido ped
                   INNER JOIN ped.carga c
                   WHERE c.codExpedicao = $Expedicao
                     AND ped.pontoTransbordo = $PontoTransbordo";

        $pedidos = $this->getEntityManager()->createQuery($query)->getResult();
        foreach ($pedidos as $pedido) {
            $pedido->setConferido(1);
            $this->_em->persist($pedido);
        }
        $this->_em->flush();
    }

    public function findPedidosNaoConferidos ($idExpedicao) {
        $query = "SELECT p
                    FROM wms:Expedicao\Pedido p
              INNER JOIN p.carga c
                   WHERE c.codExpedicao = " . $idExpedicao . "
                     AND (p.conferido = 0  OR p.conferido IS NULL)";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido
     * @return array
     */
    public function findPedidosProdutosSemEtiquetaById($idPedido)
    {
        $query = "SELECT pp
                        FROM wms:Expedicao\PedidoProduto pp
                        INNER JOIN pp.produto p
                        INNER JOIN pp.pedido ped
                        INNER JOIN ped.carga c
                        WHERE ped.id = $idPedido
                        AND ped.id NOT IN (
                          SELECT pp2.codPedido
                            FROM wms:Expedicao\EtiquetaSeparacao ep
                            INNER JOIN wms:Expedicao\PedidoProduto pp2
                            WITH pp2.pedido = ep.pedido
                         )
                        ";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido
     * @param $status
     * @return bool
     * @throws \Exception
     */
    public function gerarEtiquetasById($idPedido, $status)
    {
        $pedidosProdutos = $this->findPedidosProdutosSemEtiquetaById($idPedido);
        if ($pedidosProdutos != null) {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
            $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $pedidoEn = $this->findOneBy(array('id'=>$idPedido));
            $idModeloSeparacaoPadrao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

            if ($EtiquetaSeparacaoRepo->gerarMapaEtiqueta($pedidoEn->getCarga()->getExpedicao()->getId(), $pedidosProdutos, $status,$idModeloSeparacaoPadrao) > 0 ) {
                throw new \Exception ("Existem produtos sem definição de volume");
            }
            return true;
        }
        return false;
    }


    /**
     * @param $idPedido
     * @return mixed
     */
    public function getCargaByPedido($idPedido)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('e.id')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where('p.id = :IdPedido')
            ->setParameter('IdPedido', $idPedido);
        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param $idPedido
     */
    public function cancelar($idPedido)
    {
        try {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
            $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $etiquetas = $EtiquetaSeparacaoRepo->getEtiquetasByPedido($idPedido);

            foreach ($etiquetas as $etiqueta){
                /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetaEn */
                $etiquetaEn = $EtiquetaSeparacaoRepo->find($etiqueta['codBarras']);

                if ($etiquetaEn->getCodStatus() <> EtiquetaSeparacao::STATUS_CORTADO) {
                    if ($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                        $EtiquetaSeparacaoRepo->alteraStatus($etiquetaEn, EtiquetaSeparacao::STATUS_CORTADO);
                    } else {
                        $EtiquetaSeparacaoRepo->alteraStatus($etiquetaEn, EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                    }
                }
            }
            $this->_em->flush();
            $this->gerarEtiquetasById($idPedido, EtiquetaSeparacao::STATUS_CORTADO);
            $this->cancelaPedido($idPedido);
            $this->removeReservaEstoque($idPedido);

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * @param $idPedido
     */
    protected function cancelaPedido($idPedido)
    {
        $EntPedido = $this->find($idPedido);
        $EntPedido->setDataCancelamento(new \DateTime());
        $this->_em->persist($EntPedido);
        $this->_em->flush();
    }

    /**
     * @param Pedido $pedidoEntity
     */
    public function remove(Pedido $pedidoEntity, $runFlush = true) {

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas = $EtiquetaRepo->findBy(array('pedido'=>$pedidoEntity));
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

        foreach($etiquetas as $etiqueta) {
            $this->_em->remove($etiqueta);

            if ($runFlush == true) {
                $this->_em->flush();
            }
        }

        /*
        $EtiquetaConfRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaConferencia');
        $etiquetasConf = $EtiquetaConfRepo->findBy(array('pedido'=>$pedidoEntity));

        foreach($etiquetasConf as $etiquetaConf) {
            $this->_em->remove($etiquetaConf);
            $this->_em->flush();
        }
        */

        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
        $pedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
        $pedidoProdutoEn = $pedidoProdutoRepo->findBy(array('pedido' => $pedidoEntity));
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaProdutoRepo */
        $mapaSeparacaProdutoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebraRepository $mapaSeparacaoQuebraRepo */
        $mapaSeparacaoQuebraRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoQuebra');

        foreach ($pedidoProdutoEn as $pedidoProduto) {
            $mapaSeparacaoProdutoEn = $mapaSeparacaProdutoRepo->findOneBy(array('codPedidoProduto' => $pedidoProduto->getId()));
            if ($mapaSeparacaoProdutoEn != null) {
                $mapaSeparacaoEn = $mapaSeparacaoRepo->findOneBy(array('id' => $mapaSeparacaoProdutoEn->getMapaSeparacao()));
                $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => $mapaSeparacaoEn->getId()));

                $this->_em->remove($mapaSeparacaoQuebraEn);
                $this->_em->remove($mapaSeparacaoEn);

                $this->_em->remove($mapaSeparacaoProdutoEn);
                if ($runFlush == true) {
                    $this->_em->flush();
                }
            }
        }

        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $PedidoProdutoRepo */
        $PedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
        $pedidosProduto = $PedidoProdutoRepo->findBy(array('pedido' => $pedidoEntity->getId()));

        foreach ($pedidosProduto as $pedidoProduto) {
            $this->_em->remove($pedidoProduto);
            if ($runFlush == true) {
                $this->_em->flush();
            }
        }

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedidoRepository $ondaRessuprimentoPedidoRepo */
        $ondaRessuprimentoPedidoRepo = $this->_em->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido');
        $ondaRessuprimentoPedidoEn = $ondaRessuprimentoPedidoRepo->findOneBy(array('pedido' => $pedidoEntity));

        if (isset($ondaRessuprimentoPedidoEn) && !empty($ondaRessuprimentoPedidoEn)) {
            $this->_em->remove($ondaRessuprimentoPedidoEn);
        }

        ///andamentoRepo->save("Pedido ". $pedidoEntity->getId() ."  removido da expedição " .  $pedidoEntity->getCarga()->getExpedicao()->getId() . "via WebService", $pedidoEntity->getCarga()->getExpedicao(),false,true,null,null,true);

        $this->_em->remove($pedidoEntity);
        if ($runFlush == true) {
            $this->_em->flush();
        }
    }


    /**
     * O array de pedidos deve ter a chave o id do pedido e o value a sequencia desejada
     */
    public function realizaSequenciamento(array $pedidos)
    {
        foreach($pedidos as $chave => $sequencia)
        {
            $entityPedido = $this->find($chave);
            $entityPedido->setSequencia($sequencia);
            $this->_em->persist($entityPedido);
        }
       if ($this->_em->flush()) {
           return true;
       }
    }

    public function removeReservaEstoque($idPedido, $runFlush = true)
    {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $PedidoProdutoRepo */
        $PedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');

        $getCentralEntrega = $PedidoProdutoRepo->getFilialByProduto($idPedido);

        $ondasPedido = $this->getEntityManager()->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido')->findBy(array('pedido'=>$idPedido));
        if (count($ondasPedido) == 0) {
            return;
        }
        foreach ($ondasPedido as $ondaPedido) {
            $this->getEntityManager()->remove($ondaPedido);
        }

        foreach ($getCentralEntrega as $centralEntrega) {
            if ($centralEntrega['indUtilizaRessuprimento'] == 'S') {
                $dados['produto'] = $centralEntrega['produto'];
                $dados['grade'] = $centralEntrega['grade'];
                $dados['expedicao'] = $centralEntrega['expedicao'];

                $arrayReservaEstoqueId = $PedidoProdutoRepo->identificaExpedicaoPedido($dados);

                //atualiza a tabela RESERVA_ESTOQUE_PRODUTO que tiver o COD_RESERVA_ESTOQUE da consulta acima
                $reservaEstoqueProdutoRepository = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoqueProduto');

                foreach ($arrayReservaEstoqueId as $key => $reservaEstoqueId) {
                    $arrayReservaProdutoEntity = $reservaEstoqueProdutoRepository->findBy(array('reservaEstoque' => $reservaEstoqueId['reservaEstoque']));
                    foreach ($arrayReservaProdutoEntity as $reservaProdutoEntity) {
                        $reservaProdutoEntity->setQtd($reservaProdutoEntity->getQtd() + $centralEntrega['quantidade']);
                        $this->_em->persist($reservaProdutoEntity);

                        $reservaEstoqueRepository = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');
                        $reservaId = $reservaEstoqueRepository->findOneBy(array('id' => $reservaProdutoEntity->getReservaEstoque()));
                        if (($reservaProdutoEntity->getQtd() + $centralEntrega['quantidade']) == 0) {
                            $reservaId->setAtendida('C');
                            $this->_em->persist($reservaId);
                        }
                    }
                }
            }
        }

        if ($runFlush == true) {
            $this->_em->flush();
        }
    }


    public function getDadosPedidoByCodPedido ($codPedido){
        $SQL = "
                SELECT P.COD_PEDIDO,
                       CLI.COD_CLIENTE_EXTERNO as COD_CLIENTE,
                       PES.NOM_PESSOA as CLIENTE,
                       E.COD_EXPEDICAO,
                       C.COD_CARGA_EXTERNO,
                       E.DSC_PLACA_EXPEDICAO,
                       S.DSC_SIGLA as SITUACAO,
                       NVL(ETQ.QTD,0) as ETIQUETAS_GERADAS,
                       PROD.QTD as QTD_PRODUTOS,
                       I.DSC_ITINERARIO,
                       P.DSC_LINHA_ENTREGA,
                       ENDERECO.DSC_ENDERECO as RUA,
                       ENDERECO.NUM_ENDERECO as NUMERO,
                       ENDERECO.DSC_COMPLEMENTO as COMPLEMENTO,
                       ENDERECO.NOM_BAIRRO,
                       ENDERECO.NOM_LOCALIDADE CIDADE,
                       UF.COD_REFERENCIA_SIGLA as UF,
                       ENDERECO.NUM_CEP as CEP,
                       P.CENTRAL_ENTREGA as FILIAL_ESTOQUE,
                       P.PONTO_TRANSBORDO as FILIAL_TRANSBORDO
                  FROM PEDIDO P
                  LEFT JOIN PESSOA PES ON P.COD_PESSOA = PES.COD_PESSOA
                  LEFT JOIN CLIENTE CLI ON CLI.COD_PESSOA = PES.COD_PESSOA
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                  LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                  LEFT JOIN (SELECT COUNT(*) as QTD, COD_PEDIDO FROM PEDIDO_PRODUTO GROUP BY COD_PEDIDO) PROD ON PROD.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN (SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD, COD_PEDIDO FROM ETIQUETA_SEPARACAO GROUP BY COD_PEDIDO) ETQ ON ETQ.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN ITINERARIO I ON I.COD_ITINERARIO = P.COD_ITINERARIO
                  LEFT JOIN PESSOA_ENDERECO ENDERECO ON ENDERECO.COD_PESSOA = PES.COD_PESSOA AND ENDERECO.COD_TIPO_ENDERECO = 22
                  LEFT JOIN SIGLA UF ON UF.COD_SIGLA = ENDERECO.COD_UF
                  WHERE P.COD_PEDIDO = " . $codPedido;

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getProdutosByPedido($codPedido){
        $SQL = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               PP.QUANTIDADE
          FROM PEDIDO_PRODUTO PP
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
         WHERE PP.COD_PEDIDO = $codPedido ORDER BY COD_PRODUTO, DSC_GRADE";
        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEtiquetasByPedido($codPedido) {
        $SQL = "
        SELECT ES.COD_ETIQUETA_SEPARACAO,
               P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as EMBALAGEM,
               S.DSC_SIGLA as SITUACAO
          FROM ETIQUETA_SEPARACAO ES
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = ES.COD_PRODUTO AND P.DSC_GRADE = ES.DSC_GRADE
          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ES.COD_PRODUTO_VOLUME
          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
          LEFT JOIN SIGLA S ON S.COD_SIGLA = ES.COD_STATUS
         WHERE ES.COD_PEDIDO = $codPedido ORDER BY ES.COD_ETIQUETA_SEPARACAO";
        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getPedidoByExpedicao($idExpedicao)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, pe.nome cliente, NVL(i.descricao itinerario,\'PADRAO\')')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('wms:Pessoa','pe', 'WITH', 'pe.id = p.pessoa')
            ->leftJoin('wms:Expedicao\Itinerario', 'i', 'WITH', 'i.id = p.itinerario')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where("e.id = $idExpedicao")
            ->orderBy('p.id');

        return $sql->getQuery()->getResult();
    }

}