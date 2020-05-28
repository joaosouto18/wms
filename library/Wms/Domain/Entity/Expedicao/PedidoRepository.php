<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Domain\Entity\Produto\Embalagem;
use Wms\Domain\Entity\Produto\Lote;
use Wms\Math;
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

            $tipoPedEn = $em->getRepository('wms:Expedicao\TipoPedido')->findOneBy(['codExterno' => $pedido['tipoPedido']]);

            if (empty($tipoPedEn)) {
                throw new \Exception('O tipo de pedido '.$pedido['tipoPedido'].' não está cadastrado');
            }
            $numSequencial = $this->getMaxCodPedidoByCodExterno($pedido['codPedido'], true);
            $enPedido->setCodExterno($pedido['codPedido']);
            $enPedido->setTipoPedido($tipoPedEn);
            $enPedido->setCodTipoPedido($tipoPedEn->getId());
            $enPedido->setLinhaEntrega($pedido['linhaEntrega']);
            $enPedido->setCentralEntrega($pedido['centralEntrega']);
            $enPedido->setCarga($pedido['carga']);
            $enPedido->setItinerario($pedido['itinerario']);
            $enPedido->setPessoa($pedido['pessoa']);
            $enPedido->setPontoTransbordo($pedido['pontoTransbordo']);
            $enPedido->setEnvioParaLoja($pedido['envioParaLoja']);
            $enPedido->setIndEtiquetaMapaGerado('N');
            $enPedido->setProprietario((isset($pedido['codProprietario'])) ? $pedido['codProprietario'] : null);
            $enPedido->setNumSequencial((isset($numSequencial)) ? $numSequencial : null);
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

        $ncl = Lote::NCL;

        //regexp_replace(LPAD(PJ.NUM_CNPJ, 15, '0'),'([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})','\1.\2.\3/\4-\5') as CNPJ
        $controleProprietario = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'CONTROLE_PROPRIETARIO'))->getValor();
        if($controleProprietario == 'S'){
            $SQL = "SELECT EP.COD_PESSOA, 
                           PP.COD_PRODUTO, 
                           PP.DSC_GRADE, 
                           PP.QUANTIDADE as QTD_PEDIDO, 
                           NVL((EP.QTD * -1),0) as ATENDIDA, 
                           PJ.NUM_CNPJ as CNPJ,
                           PP.QTD_EMBALAGEM_VENDA as QTD_PEDIDO_EMBALAGEM_VENDA,
                           NVL((EP.QTD * -1),0) / NVL(PP.FATOR_EMBALAGEM_VENDA,1) as QTD_ATENDIDA_EMB_VENDA,
                           NVL(PP.FATOR_EMBALAGEM_VENDA, 1) as FATOR_EMBALAGEM_VENDA,
                           ETQ_C.QTD_CONFERIDA,
                           null as EMBALADO,
                           PP.COD_PEDIDO_PRODUTO,
                           '$ncl' as DSC_LOTE
                    FROM PEDIDO_PRODUTO PP 
                    LEFT JOIN ESTOQUE_PROPRIETARIO EP ON (PP.COD_PRODUTO = EP.COD_PRODUTO AND PP.DSC_GRADE = EP.DSC_GRADE AND PP.COD_PEDIDO = EP.COD_OPERACAO)
                    LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                    LEFT JOIN (SELECT ES.COD_PRODUTO, 
                                      ES.DSC_GRADE, 
                                      MIN(NVL(ESC.QTD,0)) as QTD_CONFERIDA
                                 FROM ETIQUETA_SEPARACAO ES
                                 LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) as VOLUME, NVL(DSC_LOTE,'$ncl') as LOTE, COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                                              FROM ETIQUETA_SEPARACAO
                                             WHERE COD_PEDIDO = '$codPedido'
                                               AND COD_STATUS IN (526,531,532)
                                             GROUP BY COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0),NVL(DSC_LOTE,'$ncl') ) ESC
                                   ON ES.COD_PRODUTO = ESC.COD_PRODUTO
                                  AND ES.DSC_GRADE = ESC.DSC_GRADE
                                  AND NVL(ES.COD_PRODUTO_VOLUME,0) = ESC.VOLUME
                                WHERE ES.COD_PEDIDO = '$codPedido'
                                GROUP BY ES.COD_PRODUTO, ES.DSC_GRADE,NVL(DSC_LOTE,'$ncl')) ETQ_C
                      ON ETQ_C.COD_PRODUTO = PP.COD_PRODUTO
                     AND ETQ_C.DSC_GRADE = PP.DSC_GRADE
                    WHERE PP.COD_PEDIDO = $codPedido";
        }else {
            $SQL = "SELECT '' as COD_PESSOA,
                           PP.COD_PRODUTO, 
                           PP.DSC_GRADE, 
                           NVL(PPL.QUANTIDADE, PP.QUANTIDADE) as QTD_PEDIDO, 
                           CASE WHEN (PPL.DSC_LOTE IS NOT NULL ) THEN PPL.QUANTIDADE - NVL(PPL.QTD_CORTE,0)
                                ELSE PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0) END as ATENDIDA, 
                           '' AS CNPJ,
                           NVL(PPL.DSC_LOTE,'$ncl') as DSC_LOTE,
                           NVL(PPL.QUANTIDADE, PP.QUANTIDADE) / NVL(PP.FATOR_EMBALAGEM_VENDA, 1) as QTD_PEDIDO_EMBALAGEM_VENDA,
                           CASE WHEN (PPL.DSC_LOTE IS NOT NULL ) THEN (PPL.QUANTIDADE - NVL(PPL.QTD_CORTE,0)) / NVL(PP.FATOR_EMBALAGEM_VENDA,1)
                                ELSE (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) / NVL(PP.FATOR_EMBALAGEM_VENDA,1) END as QTD_ATENDIDA_EMB_VENDA,
                           NVL(PP.FATOR_EMBALAGEM_VENDA, 1) as FATOR_EMBALAGEM_VENDA,
                           ETQ_C.QTD_CONFERIDA,
                           null as EMBALADO,
                           PP.COD_PEDIDO_PRODUTO              
                    FROM PEDIDO_PRODUTO PP
                    LEFT JOIN PEDIDO_PRODUTO_LOTE PPL ON PPL.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                    LEFT JOIN (SELECT ES.COD_PRODUTO, 
                                      ES.DSC_GRADE, 
                                      NVL(ES.DSC_LOTE,'$ncl') as LOTE,
                                      MIN(NVL(ESC.QTD,0)) as QTD_CONFERIDA
                                 FROM ETIQUETA_SEPARACAO ES
                                 LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) as VOLUME, NVL(DSC_LOTE,'$ncl') as LOTE, COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                                              FROM ETIQUETA_SEPARACAO
                                             WHERE COD_PEDIDO = '$codPedido'
                                               AND COD_STATUS IN (526,531,532)
                                             GROUP BY COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0),NVL(DSC_LOTE,'$ncl') ) ESC
                                   ON ES.COD_PRODUTO = ESC.COD_PRODUTO
                                  AND ES.DSC_GRADE = ESC.DSC_GRADE
                                  AND NVL(ES.COD_PRODUTO_VOLUME,0) = ESC.VOLUME
                                  AND NVL(ES.DSC_LOTE,'$ncl') = ESC.LOTE
                                WHERE ES.COD_PEDIDO = '$codPedido'
                                GROUP BY ES.COD_PRODUTO, ES.DSC_GRADE,NVL(DSC_LOTE,'$ncl')) ETQ_C
                      ON ETQ_C.COD_PRODUTO = PP.COD_PRODUTO
                     AND ETQ_C.DSC_GRADE = PP.DSC_GRADE
                     AND ETQ_C.LOTE = NVL(PPL.DSC_LOTE,'$ncl') 
                   WHERE PP.COD_PEDIDO = '$codPedido'";
        }
        $arrayPedidos = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if ($this->getSystemParameterValue('RETORNO_PRODUTO_EMBALADO') == 'N') {
            return $arrayPedidos;
        }

        $SQL = "SELECT PP.COD_PEDIDO_PRODUTO,
                       PPEC.COD_MAPA_SEPARACAO_EMBALADO as COD_EMBALADO,
                       PPEC.DSC_LOTE,
                       PPEC.QTD
                  FROM PEDIDO_PRODUTO_EMB_CLIENTE PPEC
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = PPEC.COD_PEDIDO_PRODUTO
                 WHERE PP.COD_PEDIDO = '$codPedido'";
        $arrayEmbalados = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($arrayPedidos as $keyPP => $pp) {
            foreach ($arrayEmbalados as $keyEmb => $embalado) {
                if (($pp['COD_PEDIDO_PRODUTO'] == $embalado['COD_PEDIDO_PRODUTO'])
                    && ($pp['DSC_LOTE'] == $embalado['DSC_LOTE'])) {
                    $result[] = array(
                        'COD_PESSOA' => $pp['COD_PESSOA'],
                        'COD_PRODUTO' => $pp['COD_PRODUTO'],
                        'DSC_GRADE' => $pp['DSC_GRADE'],
                        'QTD_PEDIDO' => $pp['QTD_PEDIDO'],
                        'ATENDIDA' => $embalado['QTD'],
                        'CNPJ' => $pp['CNPJ'],
                        'DSC_LOTE' => $pp['DSC_LOTE'],
                        'QTD_PEDIDO_EMBALAGEM_VENDA' => $pp['QTD_PEDIDO_EMBALAGEM_VENDA'],
                        'QTD_ATENDIDA_EMB_VENDA' => $embalado['QTD'] / $pp['FATOR_EMBALAGEM_VENDA'],
                        'FATOR_EMBALAGEM_VENDA' => $pp['FATOR_EMBALAGEM_VENDA'],
                        'QTD_CONFERIDA' => $pp['QTD_CONFERIDA'],
                        'EMBALADO' => $embalado['COD_EMBALADO']
                    );

                    $arrayPedidos[$keyPP]['ATENDIDA'] = $arrayPedidos[$keyPP]['ATENDIDA'] - $embalado['QTD'];
                    unset($arrayEmbalados[$keyEmb]);
                }
            }

            if ($arrayPedidos[$keyPP]['ATENDIDA'] >0) {
                $result[] = array(
                    'COD_PESSOA' => $pp['COD_PESSOA'],
                    'COD_PRODUTO' => $pp['COD_PRODUTO'],
                    'DSC_GRADE' => $pp['DSC_GRADE'],
                    'QTD_PEDIDO' => $pp['QTD_PEDIDO'],
                    'ATENDIDA' => $arrayPedidos[$keyPP]['ATENDIDA'] / $pp['FATOR_EMBALAGEM_VENDA'],
                    'CNPJ' => $pp['CNPJ'],
                    'DSC_LOTE' => $pp['DSC_LOTE'],
                    'QTD_PEDIDO_EMBALAGEM_VENDA' => $pp['QTD_PEDIDO_EMBALAGEM_VENDA'],
                    'QTD_ATENDIDA_EMB_VENDA' => $pp['QTD_PEDIDO_EMBALAGEM_VENDA'],
                    'FATOR_EMBALAGEM_VENDA' => $pp['FATOR_EMBALAGEM_VENDA'],
                    'QTD_CONFERIDA' => $pp['QTD_CONFERIDA'],
                    'EMBALADO' => ''
                );
            }
        }
        return $result;
    }

    public function finalizaPedidosByCentral ($PontoTransbordo, $Expedicao, $carga = null, $flush = true)
    {
        $query = "SELECT ped
                    FROM wms:Expedicao\Pedido ped
                   INNER JOIN ped.carga c
                   WHERE c.codExpedicao = $Expedicao";

        if ($PontoTransbordo != null) {
            $query .= "AND ped.pontoTransbordo = $PontoTransbordo";

        }
        if ($carga != null) {
            $query = $query . " AND c.id = " . $carga;
        }

        $pedidos = $this->getEntityManager()->createQuery($query)->getResult();
        foreach ($pedidos as $pedido) {
            $pedido->setConferido(1);
            $this->_em->persist($pedido);
        }

        if ($flush == true) {
            $this->_em->flush();
        }
    }

    public function findPedidosNaoConferidos ($idExpedicao, $idCarga = null) {
        $sqlCarga = "";
        if ($idCarga != null) {
            $sqlCarga = " AND c.id =" . $idCarga;
        }

        $query = "SELECT p
                    FROM wms:Expedicao\Pedido p
              INNER JOIN p.carga c
                   WHERE c.codExpedicao = " . $idExpedicao . $sqlCarga . "
                     AND (p.conferido = 0  OR p.conferido IS NULL)";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido Código interno do pedido
     * @return array
     */
    public function findPedidosProdutosSemEtiquetaById($idPedido)
    {
        $query = "SELECT pp
                        FROM wms:Expedicao\PedidoProduto pp
                        INNER JOIN pp.produto p
                        INNER JOIN pp.pedido ped
                        INNER JOIN ped.carga c
                        WHERE ped.id = '$idPedido'
                        AND ped.id NOT IN (
                          SELECT pp2.codPedido
                            FROM wms:Expedicao\EtiquetaSeparacao ep
                            INNER JOIN wms:Expedicao\PedidoProduto pp2
                            WITH pp2.pedido = ep.pedido
                         )
                        AND ped.dataCancelamento is null
                        ";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido Código interno do pedido
     * @param $status
     * @return bool
     * @throws \Exception
     */
    public function gerarEtiquetasById($idPedido, $status)
    {
        try {
            $pedidosProdutos = $this->findPedidosProdutosSemEtiquetaById($idPedido);
            if ($pedidosProdutos != null) {
                /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
                $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
                $pedidoEn = $this->findOneBy(array('id' => $idPedido));
                $idModeloSeparacaoPadrao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

                $em = $this->getEntityManager();

                $arrayRepositorios = array(
                    'expedicao'           => $em->getRepository('wms:Expedicao'),
                    'filial'              => $em->getRepository('wms:Filial'),
                    'etiquetaSeparacao'   => $em->getRepository('wms:Expedicao\EtiquetaSeparacao'),
                    'depositoEndereco'    => $em->getRepository('wms:Deposito\Endereco'),
                    'modeloSeparacao'     => $em->getRepository('wms:Expedicao\ModeloSeparacao'),
                    'etiquetaConferencia' => $em->getRepository('wms:Expedicao\EtiquetaConferencia'),
                    'produtoEmbalagem'    => $em->getRepository('wms:Produto\Embalagem'),
                    'mapaSeparacaoProduto'=> $em->getRepository('wms:Expedicao\MapaSeparacaoProduto'),
                    'mapaSeparacaoPedido' => $em->getRepository('wms:Expedicao\MapaSeparacaoPedido'),
                    'cliente'             => $em->getRepository('wms:Pessoa\Papel\Cliente'),
                    'praca'               => $em->getRepository('wms:MapaSeparacao\Praca'),
                    'mapaSeparacao'       => $em->getRepository('wms:Expedicao\MapaSeparacao'),
                    'andamentoNf'         => $em->getRepository('wms:Expedicao\NotaFiscalSaidaAndamento'),
                    'reentrega'           => $em->getRepository('wms:Expedicao\Reentrega'),
                    'pedidoProduto'       => $em->getRepository('wms:Expedicao\PedidoProduto'),
                    'nfPedido'            => $em->getRepository('wms:Expedicao\NotaFiscalSaidaPedido'),
                    'nfSaida'             => $em->getRepository('wms:Expedicao\NotaFiscalSaida'),
                    'produto'             => $em->getRepository('wms:Produto')
                );

                $statusExpedicao = $pedidoEn->getCarga()->getExpedicao()->getStatus()->getId();

                if  (in_array($statusExpedicao, [Expedicao::STATUS_EM_CONFERENCIA, Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_PRIMEIRA_CONFERENCIA])) {
                    if ($EtiquetaSeparacaoRepo->gerarMapaEtiqueta($pedidoEn->getCarga()->getExpedicao()->getId(), $pedidosProdutos, $status,$idModeloSeparacaoPadrao, $arrayRepositorios) > 0 ) {
                        throw new \Exception ("Existem produtos sem definição de volume");
                    }
                }
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }
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
     * @param $idPedido Código interno do pedido
     */
    public function cancelar($idPedido, $webService = true)
    {
        try {

            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo  */
            $mapaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');

            if ($mapaSeparacaoRepo->validaMapasCortados($idPedido) == false) {
                /** @var Pedido $pedidoEn */
                $pedidoEn = $this->find($idPedido);
                $codExterno = $pedidoEn->getCodExterno();
                throw new \Exception("Pedido $codExterno precisa ser cortado no WMS");
            }

            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
            $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $etiquetas = $EtiquetaSeparacaoRepo->getEtiquetasByPedido($idPedido);

            if (isset($etiquetas) && !empty($etiquetas)) {
                foreach ($etiquetas as $etiqueta) {
                    /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetaEn */
                    $etiquetaEn = $EtiquetaSeparacaoRepo->find($etiqueta['codBarras']);

                    if ($etiquetaEn->getCodStatus() <> EtiquetaSeparacao::STATUS_CORTADO) {
                        if ($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                            $this->_em->remove($etiquetaEn);
                        } else {
                            $EtiquetaSeparacaoRepo->alteraStatus($etiquetaEn, EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                        }
                    }
                }
            }
            $this->_em->flush();
            $this->gerarEtiquetasById($idPedido, EtiquetaSeparacao::STATUS_CORTADO);
            $this->removeReservaEstoque($idPedido, true);
            $this->cancelaPedido($idPedido, $webService);

        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * @param $idPedido Código interno do pedido
     */
    protected function cancelaPedido($idPedido, $webService = true)
    {

        $expedicaoAndamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');

        $SQL = "SELECT *
                  FROM MAPA_SEPARACAO_PEDIDO MSP
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                 WHERE PP.COD_PEDIDO = '$idPedido'
                   AND PP.QUANTIDADE = NVL(PP.QTD_CORTADA,0) ";
        $countMapas = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $SQL = "SELECT *
                  FROM ETIQUETA_SEPARACAO ES
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = ES.COD_PEDIDO 
                                             AND PP.COD_PRODUTO = ES.COD_PRODUTO
                                             AND PP.DSC_GRADE = ES.DSC_GRADE
                 WHERE PP.COD_PEDIDO = '" . $idPedido . "'";
        $countEtiquetas = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        /** @var Pedido $EntPedido */
        $EntPedido = $this->find($idPedido);
        $codExternoPedido = $EntPedido->getCodExterno();
        $idExpedicao = $EntPedido->getCarga()->getExpedicao()->getId();
        $idCarga = $EntPedido->getCarga()->getId();
        $EntPedido->setDataCancelamento(new \DateTime());
        $this->_em->persist($EntPedido);


        if ((count($countMapas) == 0) && (count($countEtiquetas) == 0)) {
            /** @var PedidoProdutoRepository $pedidoProdRepo */
            $pedidoProdRepo = $this->_em->getRepository("wms:Expedicao\PedidoProduto");
            $itens = $pedidoProdRepo->findBy(array("pedido" => $EntPedido));
            foreach ($itens as $item) {
                $this->_em->remove($item);
            }
            $this->_em->remove($EntPedido);
        }

        if ($webService == true) {
            $idUsuario = $this->getSystemParameterValue('ID_USER_ERP');
            $expedicaoAndamentoRepo->save("Pedido $codExternoPedido cancelado da Expedição: $idExpedicao, Carga: $idCarga via integração", $idExpedicao, $idUsuario);
        } else {
            $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
            $expedicaoAndamentoRepo->save("Pedido $codExternoPedido cancelado da Expedição: $idExpedicao, Carga: $idCarga manualmente ", $idExpedicao, $idUsuario);
        }

        $this->_em->flush();
    }

    /**
     * @param Pedido $pedidoEntity
     */
    public function remove(Pedido $pedidoEntity, $runFlush = true)
    {

        //REPOSITORIOS
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaProdutoRepo */
        $mapaSeparacaProdutoRepository = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepository */
        $mapaSeparacaoConferenciaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebraRepository $mapaSeparacaoQuebraRepo */
        $mapaSeparacaoQuebraRepository = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $etiquetaConferenciaRepository */
        $etiquetaConferenciaRepository = $this->_em->getRepository('wms:Expedicao\EtiquetaConferencia');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
        $pedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoLoteRepository $pedidoProdutoLoteRepo */
        $pedidoProdutoLoteRepo = $this->_em->getRepository('wms:Expedicao\PedidoProdutoLote');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoEnderecoRepository $pedidoEnderecoRepository */
        $pedidoEnderecoRepository = $this->_em->getRepository('wms:Expedicao\PedidoEndereco');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoPedidoRepository $mapaSeparacaoPedidoRepository */
        $mapaSeparacaoPedidoRepository = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoPedido');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepository */
        $mapaSeparacaoEmbaladoRepository = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedidoRepository $ondaRessuprimentoPedidoRepo */
        $ondaRessuprimentoPedidoRepo = $this->_em->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido');


        // APAGA ETIQUETA_CONFERENCIA E ETIQUETA_SEPARACAO CASO EXISTA
        $etiquetaEntities = $EtiquetaRepo->findBy(array('pedido'=>$pedidoEntity));
        foreach($etiquetaEntities as $etiquetaEntity) {
            $etiquetaConferenciaEntities = $etiquetaConferenciaRepository->findBy(array('codEtiquetaSeparacao' => $etiquetaEntity->getId()));
            foreach ($etiquetaConferenciaEntities as $etiquetaConferenciaEntity) {
                $this->_em->remove($etiquetaConferenciaEntity);
            }
            $this->_em->remove($etiquetaEntity);
        }

        /* APAGA CASO EXISTAM
         * MAPA_SEPARACAO_PEDIDO
         * PEDIDO_PRODUTO_LOTE
         * */
        $pedidoProdutoEntities = $pedidoProdutoRepo->findBy(array('pedido' => $pedidoEntity));
        foreach ($pedidoProdutoEntities as $pedidoProdutoEntity) {
            $mapaSeparacaoPedidoEntities = $mapaSeparacaoPedidoRepository->findBy(array('pedidoProduto' => $pedidoProdutoEntity));
            $mapasRemover = array();

            foreach ($mapaSeparacaoPedidoEntities as $mapaSeparacaoPedidoEntity) {
                $mapaSeparacaoEntity = $mapaSeparacaoPedidoEntity->getMapaSeparacao();
                if (!isset($mapasRemover[$mapaSeparacaoEntity->getId()])){
                    $mapasRemover[$mapaSeparacaoEntity->getId()] = $mapaSeparacaoEntity;
                }
                $this->_em->remove($mapaSeparacaoPedidoEntity);
            }

            $pedProdLotes = $pedidoProdutoLoteRepo->findBy(['pedidoProduto' => $pedidoProdutoEntity]);
            foreach ($pedProdLotes as $pedProdLote) {
                $this->_em->remove($pedProdLote);
            }
        }

        /* APAGA CASO EXISTAM
         * MAPA_SEPARACAO_CONFERENCIA
         * MAPA_SEPARACAO_PRODUTO
         * MAPA_SEPARACAO_QUEBRA
         * MAPA_SEPARACAO_EMB_CLIENTE
         * MAPA_SEPARACAO
         * */
        foreach ($mapasRemover as $mapaSeparacaoEntity) {
            $mapaSeparacaoConferenciaEntities = $mapaSeparacaoConferenciaRepository->findBy(array('codMapaSeparacao' => $mapaSeparacaoEntity->getId()));
            foreach ($mapaSeparacaoConferenciaEntities as $mapaSeparacaoConferenciaEntity) {
                $this->_em->remove($mapaSeparacaoConferenciaEntity);
            }

            $mapaSeparacaoEmbaladoEntities = $mapaSeparacaoEmbaladoRepository->findBy(array('mapaSeparacao' => $mapaSeparacaoEntity));
            foreach ($mapaSeparacaoEmbaladoEntities as $mapaSeparacaoEmbaladoEntity) {
                $this->_em->remove($mapaSeparacaoEmbaladoEntity);
            }

            $mapaSeparacaoQuebraEntities = $mapaSeparacaoQuebraRepository->findBy(array('mapaSeparacao' => $mapaSeparacaoEntity));
            foreach ($mapaSeparacaoQuebraEntities as $mapaSeparacaoQuebraEntity) {
                $this->_em->remove($mapaSeparacaoQuebraEntity);
            }

            $mapaSeparacaoProdutoEntities = $mapaSeparacaProdutoRepository->findBy(array('mapaSeparacao' => $mapaSeparacaoEntity));
            foreach ($mapaSeparacaoProdutoEntities as $mapaSeparacaoProdutoEntity) {
                $this->_em->remove($mapaSeparacaoProdutoEntity);
            }
            $this->_em->remove($mapaSeparacaoEntity);
        }

        foreach ($pedidoProdutoEntities as $pedidoProdutoEntity) {
            $this->_em->remove($pedidoProdutoEntity);
        }

        //APAGA ONDA DE RESSUPRIMENTO PEDIDO
        $ondaRessuprimentoPedidoEntity = $ondaRessuprimentoPedidoRepo->findOneBy(array('pedido' => $pedidoEntity));
        if (isset($ondaRessuprimentoPedidoEntity) && !empty($ondaRessuprimentoPedidoEntity)) {
            $this->_em->remove($ondaRessuprimentoPedidoEntity);
        }

        //APAGA PEDIDO_ENDERECO
        $pedidoEnderecoEntity = $pedidoEnderecoRepository->findOneBy(array('pedido' => $pedidoEntity));
        if (isset($pedidoEnderecoEntity) && !empty($pedidoEnderecoEntity)) {
            $this->_em->remove($pedidoEnderecoEntity);
        }

        //APAGA PEDIDO
        $this->_em->remove($pedidoEntity);

        //FAZ ALTERAÇÕES NO BD
        if ($runFlush == true) {
            $this->_em->flush();
        }

        return true;
    }

    /**
     * @param Pedido $pedidoEntity
     */
    public function removeOld(Pedido $pedidoEntity, $runFlush = true) {

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
    public function realizaSequenciamento(array $pedidos,$codExpedicao)
    {
        foreach($pedidos as $chave => $sequencia)
        {
            $result = $this->getPedidosByClienteExpedicao($chave,$codExpedicao);
            foreach ($result as $pedido) {
                $pedido->setSequencia($sequencia);
                $this->_em->persist($pedido);
            }
        }
       if ($this->_em->flush()) {
           return true;
       }
    }

    /**
     * @param $codClientes
     * @param $codExpedicao
     * @return Pedido[]
     */
    private function getPedidosByClienteExpedicao($codClientes,$codExpedicao)
    {
        $clienteExternoArr = array();
        $arr = explode(",", $codClientes);
        foreach ($arr as $codCliente) {
            $clienteExternoArr[] = "'$codCliente'";
        }
        $codClienteExterno = implode(',',$clienteExternoArr);
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ped')
            ->from('wms:Expedicao\Pedido', 'ped')
            ->innerJoin('ped.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->innerJoin('ped.pessoa', 'p')
            ->innerJoin('wms:Pessoa\Papel\Cliente', 'cli', 'WITH', 'cli.id = p.id')
            ->where("e.id = $codExpedicao")
            ->andWhere("cli.codClienteExterno IN ($codClientes)");
        
        return $sql->getQuery()->getResult();
    }

    public function removeReservaEstoque($idPedido, $runFlush = true)
    {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $PedidoRepo */
        /** @var \Wms\Domain\Entity\Expedicao\Pedido $pedidoEn */

        $PedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');

        $pedidoEn = $PedidoRepo->find($idPedido);
        if (!isset($pedidoEn)) {
            throw new \Exception("Pedido não encontrado no WMS");
        }
        $idExpedicao = $pedidoEn->getCarga()->getExpedicao()->getId();

        if ($pedidoEn == null) {
            return;
        }

        $ondasPedido = $this->getEntityManager()->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido')->findBy(array('pedido'=>$idPedido));
        $reservasExpedicaoEn = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueExpedicao')->findBy(array('expedicao'=>$idExpedicao,
            'pedido'=>$idPedido));


        foreach ($reservasExpedicaoEn as $reservaExpedicaoEn) {
            $reservaEn = $reservaExpedicaoEn->getReservaEstoque();
            $this->getEntityManager()->remove($reservaExpedicaoEn);
            if ($reservaEn->getAtendida() == "N") {
                $reservaEn->setAtendida('C');
                $this->getEntityManager()->persist($reservaEn);
            }
        }

        if ($runFlush == true) {
            $this->_em->flush();
        }

        foreach ($ondasPedido as $ondaPedido) {
            $this->getEntityManager()->remove($ondaPedido);
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
                       P.PONTO_TRANSBORDO as FILIAL_TRANSBORDO,
                       PESO.NUM_PESO,
                       PESO.NUM_CUBAGEM
                  FROM PEDIDO P
                  LEFT JOIN PESSOA PES ON P.COD_PESSOA = PES.COD_PESSOA
                  LEFT JOIN CLIENTE CLI ON CLI.COD_PESSOA = PES.COD_PESSOA
                  LEFT JOIN (SELECT PP.COD_PEDIDO,
                                    SUM((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_PESO,0)) as NUM_PESO,
                                    SUM((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_CUBAGEM,0)) as NUM_CUBAGEM
                               FROM PEDIDO_PRODUTO PP
                               LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO
                                                          AND PESO.DSC_GRADE = PP.DSC_GRADE
                              GROUP BY PP.COD_PEDIDO) PESO ON PESO.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                  LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                  LEFT JOIN (SELECT COUNT(*) as QTD, COD_PEDIDO FROM PEDIDO_PRODUTO GROUP BY COD_PEDIDO) PROD ON PROD.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN (SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD, COD_PEDIDO FROM ETIQUETA_SEPARACAO GROUP BY COD_PEDIDO) ETQ ON ETQ.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN ITINERARIO I ON I.COD_ITINERARIO = P.COD_ITINERARIO
                  LEFT JOIN PESSOA_ENDERECO ENDERECO ON ENDERECO.COD_PESSOA = PES.COD_PESSOA AND ENDERECO.COD_TIPO_ENDERECO = 22
                  LEFT JOIN SIGLA UF ON UF.COD_SIGLA = ENDERECO.COD_UF
                  WHERE P.COD_EXTERNO = '" . $codPedido . "'";

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getProdutosByPedido($codPedido){

        $sqlCampoQuantidadePedido = " PP.QUANTIDADE , ";
        $sqlCampoQuantidadeCortada = " NVL(PP.QTD_CORTADA,0) as QTD_CORTADA, ";
        if ($this->getSystemParameterValue('MOVIMENTA_EMBALAGEM_VENDA_PEDIDO') == 'S') {
            $sqlCampoQuantidadePedido = "CASE WHEN (P.COD_TIPO_COMERCIALIZACAO = 1) THEN PP.QTD_EMBALAGEM_VENDA ||' ' || NVL(PE.DSC_EMBALAGEM,'') || '(' || PP.FATOR_EMBALAGEM_VENDA || ')' ELSE PP.QUANTIDADE || '' END as QUANTIDADE , ";
            $sqlCampoQuantidadeCortada = "CASE WHEN (P.COD_TIPO_COMERCIALIZACAO = 1) AND (NVL(PP.QTD_CORTADA,0) > 0) THEN (NVL(PP.QTD_CORTADA,0) / NVL(PP.FATOR_EMBALAGEM_VENDA,1)) || ' ' || NVL(PE.DSC_EMBALAGEM,'') || '(' || PP.FATOR_EMBALAGEM_VENDA || ')' ELSE NVL(PP.QTD_CORTADA,0) || '' END as QTD_CORTADA , ";
        }

        $SQL = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               $sqlCampoQuantidadePedido
               $sqlCampoQuantidadeCortada
               (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_PESO,0) as NUM_PESO,
               (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_CUBAGEM,0) as NUM_CUBAGEM
          FROM PEDIDO_PRODUTO PP
         INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = PP.COD_PEDIDO
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO AND PESO.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN (SELECT QTD_EMBALAGEM, 
                            COD_PRODUTO,
                            DSC_GRADE,
                            MAX(COD_PRODUTO_EMBALAGEM) as COD_PRODUTO_EMBALAGEM
                       FROM PRODUTO_EMBALAGEM 
                      WHERE DTH_INATIVACAO IS NULL
                      GROUP BY QTD_EMBALAGEM, COD_PRODUTO, DSC_GRADE) MP
                 ON MP.COD_PRODUTO = P.COD_PRODUTO
                AND MP.DSC_GRADE = P.DSC_GRADE
                AND MP.QTD_EMBALAGEM = PP.FATOR_EMBALAGEM_VENDA
          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MP.COD_PRODUTO_EMBALAGEM
         WHERE PED.COD_EXTERNO = '$codPedido' ORDER BY COD_PRODUTO, DSC_GRADE";
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
          INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = ES.COD_PEDIDO
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = ES.COD_PRODUTO AND P.DSC_GRADE = ES.DSC_GRADE
          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ES.COD_PRODUTO_VOLUME
          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
          LEFT JOIN SIGLA S ON S.COD_SIGLA = ES.COD_STATUS
         WHERE PED.COD_EXTERNO = '$codPedido' ORDER BY ES.COD_ETIQUETA_SEPARACAO";
        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getPedidoByExpedicao($idExpedicao, $codProduto, $grade = 'UNICA', $todosProdutos = false, $idPedido = null, $quebraEndereco = false)
    {

        try {
            $sqlCampos = "
                    P.COD_EXTERNO as \"id\",
                    CL.COD_CLIENTE_EXTERNO as \"codcli\",
                    PE.NOM_PESSOA as \"cliente\",
                    NVL(I.DSC_ITINERARIO,'PADRAO') as \"itinerario\",
                    P.NUM_SEQUENCIAL as \"numSequencial\"";
            if (!empty($codProduto)) {
                $sqlCampos = "
                    P.COD_PEDIDO as \"ID\",
                    P.COD_EXTERNO as \"id\",
                    CL.COD_PESSOA as \"idCliente\",
                    CL.COD_CLIENTE_EXTERNO as \"codcli\",
                    MS.COD_MAPA_SEPARACAO as \"mapa\",
                    MSQ.COD_MAPA_SEPARACAO as \"consolidado\",
                    PE.NOM_PESSOA as \"cliente\",
                    NVL(I.DSC_ITINERARIO,'PADRAO') as \"itinerario\",
                    P.NUM_SEQUENCIAL as \"numSequencial\",
                    NVL(MSC.QTD_CONFERIDA,0) as \"qtdConf\",
                    NVL(PP.QTD_CORTADA,0) as \"qtdCorteTotal\",
                    PP.FATOR_EMBALAGEM_VENDA as \"fatorEmbalagemVenda\",
                    C.COD_CARGA_EXTERNO as \"carga\",";

                if ($quebraEndereco) {
                    $sqlCampos .= "
                    NVL((MSPROD.QTD_SEPARAR * MSPROD.QTD_EMBALAGEM), PP.QUANTIDADE) as \"quantidade\",
                    NVL(MSPROD.QTD_CORTADO, NVL(PP.QTD_CORTADA, 0)) as \"qtdCortada\",
                    DE.COD_DEPOSITO_ENDERECO as \"idEndereco\",
                    DE.DSC_DEPOSITO_ENDERECO as \"dscEndereco\"";
                } else {
                    $sqlCampos .= "
                    NVL(MSP.QTD, PP.QUANTIDADE) as \"quantidade\",
                    NVL(MSP.QTD_CORTADA, NVL(PP.QTD_CORTADA, 0)) as \"qtdCortada\"";
                }
            }

            $sql = "SELECT DISTINCT $sqlCampos FROM PEDIDO_PRODUTO PP
              INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
              INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
              INNER JOIN PESSOA PE ON PE.COD_PESSOA = P.COD_PESSOA
              INNER JOIN CLIENTE CL ON P.COD_PESSOA = CL.COD_PESSOA
               LEFT JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO ";

            if (!empty($codProduto)) {
                $sql .= "LEFT JOIN MAPA_SEPARACAO_PEDIDO MSP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
               LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
               LEFT JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MSQ.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSQ.IND_TIPO_QUEBRA = '" . MapaSeparacaoQuebra::QUEBRA_CARRINHO . "'
               LEFT JOIN (SELECT M.COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) QTD_CONFERIDA, NVL(MSC2.COD_PESSOA, 0) COD_CLIENTE
                            FROM MAPA_SEPARACAO_CONFERENCIA MSC2 INNER JOIN MAPA_SEPARACAO M on MSC2.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                           WHERE M.COD_EXPEDICAO in ($idExpedicao)
                           GROUP BY M.COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(MSC2.COD_PESSOA, 0)) MSC
                         ON MS.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PP.COD_PRODUTO AND MSC.DSC_GRADE = PP.DSC_GRADE
                         AND CASE WHEN MSC.COD_CLIENTE = 0 THEN 1 ELSE CASE WHEN MSC.COD_CLIENTE = P.COD_PESSOA THEN 1 ELSE 0 END END = 1                          
                   ";
            }

            if ($quebraEndereco) {
                $sql .= "
               LEFT JOIN MAPA_SEPARACAO_PRODUTO MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PRODUTO = '$codProduto' AND MSPROD.DSC_GRADE = '$grade'  AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
               LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = MSPROD.COD_DEPOSITO_ENDERECO
               ";
            }

            $where = " WHERE C.COD_EXPEDICAO in($idExpedicao)";

            if ($todosProdutos == false) {
                $where .= " AND PP.QUANTIDADE > NVL(PP.QTD_CORTADA, 0)";
            }

            if (!empty($idPedido)) {
                $where .= " AND P.COD_PEDIDO = $idPedido";
            }

            $groupBy = "";
            if (isset($codProduto) && !empty($codProduto)) {
                $where .= " AND PP.COD_PRODUTO = '$codProduto' AND PP.DSC_GRADE = '$grade'";
            } else {
                $groupBy = 'GROUP BY P.COD_EXTERNO, PE.NOM_PESSOA, I.DSC_ITINERARIO, P.NUM_SEQUENCIAL, CL.COD_CLIENTE_EXTERNO';
            }

            $orderBy = "ORDER BY CL.COD_CLIENTE_EXTERNO, P.COD_EXTERNO";

            if (!empty($codProduto)) {
                $orderBy .= ", MS.COD_MAPA_SEPARACAO";
                if ($quebraEndereco) $orderBy .= ", DE.DSC_DEPOSITO_ENDERECO";
            }

            $result = $this->_em->getConnection()->query("$sql $where $groupBy $orderBy")->fetchAll();

            if (isset($codProduto) && !empty($codProduto)) {
                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
                if ($this->getSystemParameterValue('MOVIMENTA_EMBALAGEM_VENDA_PEDIDO') != 'S') {
                    $embalagemEn = null;
                    $embalagens = $embalagemRepo->findBy(array('codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null), array('quantidade' => 'ASC'));
                    if ($embalagens != null) {
                        $embalagemEn = $embalagens[0];
                    }

                    foreach ($result as $key => $value) {
                        $result[$key]['quantidadeUnitaria'] = $value['quantidade'];
                        $result[$key]['qtdCortadaUnitaria'] = $value['qtdCortada'];
                        $result[$key]['qtdCorteTotalUnitaria'] = $value['qtdCorteTotal'];

                        if ($embalagemEn == null) {
                            $result[$key]['fatorEmbalagemVenda'] = 1;
                        } else {
                            $result[$key]['fatorEmbalagemVenda'] = $embalagemEn->getQuantidade();
                        }

                        $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($codProduto, $grade, $value['quantidade']);
                        if (is_array($vetEmbalagens)) {
                            $embalagem = implode(' + ', $vetEmbalagens);
                        } else {
                            $embalagem = $vetEmbalagens;
                        }
                        $result[$key]['quantidade'] = $embalagem;

                        $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($codProduto, $grade, $value['qtdCortada']);
                        if (is_array($vetEmbalagens)) {
                            $embalagem = implode(' + ', $vetEmbalagens);
                        } else {
                            $embalagem = $vetEmbalagens;
                        }
                        $result[$key]['qtdCortada'] = $embalagem;

                        $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($codProduto, $grade, $value['qtdCorteTotal']);
                        if (is_array($vetEmbalagens)) {
                            $embalagem = implode(' + ', $vetEmbalagens);
                        } else {
                            $embalagem = $vetEmbalagens;
                        }
                        $result[$key]['qtdCorteTotal'] = $embalagem;

                        if ($embalagemEn == null) {
                            $result[$key]['idEmbalagem'] = "";
                        } else {
                            $result[$key]['idEmbalagem'] = $embalagemEn->getId();
                        }
                    }
                } else {
                    foreach ($result as $key => $value) {
                        $result[$key]['quantidadeUnitaria'] = $value['quantidade'];
                        $result[$key]['qtdCortadaUnitaria'] = $value['qtdCortada'];

                        $fatorEmbalagem = $result[$key]['fatorEmbalagemVenda'];
                        $embalagemEn = $embalagemRepo->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null, 'quantidade' => $fatorEmbalagem));

                        if ($embalagemEn == null) {
                            $result[$key]['idEmbalagem'] = "";
                        } else {
                            $result[$key]['idEmbalagem'] = $embalagemEn->getId();
                        }

                        $dscEmbalagem = " ";
                        if ($embalagemEn != null) {
                            $dscEmbalagem = $embalagemEn->getDescricao();
                            $dscEmbalagem = " $dscEmbalagem($fatorEmbalagem)";
                        }

                        $result[$key]['quantidade'] = ($result[$key]['quantidade'] / $fatorEmbalagem) . $dscEmbalagem;
                        if ($result[$key]['qtdCortada'] <> 0) {
                            $result[$key]['qtdCortada'] = ($result[$key]['qtdCortada'] / $fatorEmbalagem) . $dscEmbalagem;
                        } else {
                            $result[$key]['qtdCortada'] = "";
                        }
                    }
                }
            }

            foreach ($result as $key => $value) {
                if (!empty($value['numSequencial']) && $value['numSequencial'] > 1) {
                    $result[$key]['id'] = $value['id'] . ' - ' . $value['numSequencial'];
                }
            }
        }catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    public function getSituacaoPedido ($idPedido) {

        $sql = "SELECT DISTINCT
                    E.COD_EXPEDICAO
                FROM EXPEDICAO E
                INNER JOIN CARGA C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO AND PP.QUANTIDADE != PP.QTD_CORTADA
                LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                LEFT JOIN (
                    SELECT MSP.COD_MAPA_SEPARACAO, SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM)- MSP.QTD_CORTADO) AS QTD_SEPARAR
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    GROUP BY MSP.COD_MAPA_SEPARACAO) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                    SELECT COD_MAPA_SEPARACAO, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                    FROM MAPA_SEPARACAO_CONFERENCIA
                    GROUP BY COD_MAPA_SEPARACAO ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN ETIQUETA_SEPARACAO ES ON ES.COD_PEDIDO = PP.COD_PEDIDO AND ES.COD_PRODUTO = PP.COD_PRODUTO AND ES.DSC_GRADE = PP.DSC_GRADE
                WHERE P.COD_PEDIDO = '$idPedido' AND ((MSP.QTD_SEPARAR != MSC.QTD_CONF OR ES.COD_STATUS NOT IN (524, 525, 526, 531, 532, 552))
                      OR (PP.COD_PEDIDO_PRODUTO NOT IN (SELECT COD_PEDIDO_PRODUTO FROM MAPA_SEPARACAO_PEDIDO) AND PP.COD_PEDIDO_PRODUTO NOT IN
                        (SELECT PP2.COD_PEDIDO_PRODUTO 
                         FROM ETIQUETA_SEPARACAO ES2 
                         INNER JOIN PEDIDO_PRODUTO PP2 ON PP2.COD_PEDIDO = ES2.COD_PEDIDO AND PP2.COD_PRODUTO = ES2.COD_PRODUTO AND PP2.DSC_GRADE = ES2.DSC_GRADE)))";

        $result = $this->_em->getConnection()->query($sql)->fetchAll();

        if (empty($result))
            return true;

        return false;
    }

    public function getMaxCodPedidoByCodExterno($idPedidoExterno, $numSequencial = false){
        $sql = "SELECT COD_PEDIDO, NUM_SEQUENCIAL FROM PEDIDO WHERE COD_EXTERNO = '$idPedidoExterno' ORDER BY NUM_SEQUENCIAL DESC ";
        $result = $this->_em->getConnection()->query($sql)->fetch();
        if($numSequencial == true){
            $numSequencial = null;
            if(!empty($result)){
                if($result['NUM_SEQUENCIAL'] == null){
                    $numSequencial = 2;
                }else{
                    $numSequencial = $result['NUM_SEQUENCIAL'] + 1;
                }
            }
            return $numSequencial;
        }else {
            return $result['COD_PEDIDO'];
        }
    }

    public function comparaPedidos($produtosNovos, $produtosAntigos){
        foreach ($produtosNovos as $newProd){
            foreach ($produtosAntigos as $oldProd){
                if($newProd['codProduto'] == $oldProd['COD_PRODUTO'] && $newProd['grade'] == $oldProd['DSC_GRADE'] && $newProd['lote'] == $oldProd['DSC_LOTE']){
                    return true;
                }
                return false;
            }
        }
        return true;
    }

    public function permiteAlterarPedidos($pedidoIntegracao, $PedidoEntity)
    {
        $permiteAlterarPedidos = $this->getSystemParameterValue('PERMITIR_ALTERAR_PEDIDOS');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepository */
        $pedidoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoPedidoRepository $mapaSeparacaoPedidoRepositoty */
        $mapaSeparacaoPedidoRepositoty = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoPedido');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepository */
        $mapaSeparacaoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProdutoRepository $reservaEstoqueProdutoRepository */
        $reservaEstoqueProdutoRepository = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueProduto');

        try {
            if ($permiteAlterarPedidos === 'S') {
                foreach ($pedidoIntegracao['produtos'] as $produto) {
                    //localiza o produto alterado no pedido
                    $pedidoProdutoEntity = $pedidoProdutoRepository->findOneBy(array('codPedido' => $PedidoEntity->getId(), 'codProduto' => $produto['codProduto'], 'grade' => $produto['grade']));
                    if (isset($pedidoProdutoEntity)) {
                        //verifica se a nova quantidade é maior do que a quantidade existente
                        if ($produto['quantidade'] > $pedidoProdutoEntity->getQuantidade()) {
                            //altera a PEDIDO_PRODUTO com a nova quantidade bruta
                            $pedidoProdutoEntity->setQuantidade($produto['quantidade']);
                            $this->getEntityManager()->persist($pedidoProdutoEntity);

                            //localiza todos os MAPA_SEPARACAO_PEDIDO
                            $mapaSeparacaoPedidoEntities = $mapaSeparacaoPedidoRepositoty->findBy(array('pedidoProduto' => $pedidoProdutoEntity));
                            //soma as quantidades existentes nos MAPA_SEPARACAO_PEDIDO antes de fazer alteração
                            $quantidadeExistente = 0;
                            foreach ($mapaSeparacaoPedidoEntities as $k => $mapaSeparacaoPedidoEntity) {
                                $quantidadeExistente = $quantidadeExistente + $mapaSeparacaoPedidoEntity->getQtd();
                                if ($mapaSeparacaoPedidoEntity === reset($mapaSeparacaoPedidoEntities)) {
                                    $mapaPedidoEntity = $mapaSeparacaoPedidoEntity;
                                }
                            }
                            //subtrai a nova quantidade bruta da quantidade que ja existia nos MAPA_SEPARACAO_PEDIDO para saber a quantidade q será inserida em um unico mapa
                            $novaQuantidade = Math::subtrair($produto['quantidade'], $quantidadeExistente);
                            //atualiza o MAPA_SEPARACAO_PEDIDO inserindo a nova qunatidade somado a quantidade do primeiro MAPA_SEPARACAO_PEDIDO encontrado
                            $mapaPedidoEntity->setQtd(Math::adicionar($mapaPedidoEntity->getQtd(), $novaQuantidade));
                            $this->getEntityManager()->persist($mapaPedidoEntity);

                            //localiza o MAPA_SEPARACAO_PRODUTO baseando no MAPA_SEPARACAO_PEDIDO e no produto alterado
                            $mapaSeparacaoProdutoEntity = $mapaSeparacaoProdutoRepository->findOneBy(array('mapaSeparacao' => $mapaSeparacaoPedidoEntity->getMapaSeparacao(), 'codProduto' => $produto['codProduto'], 'dscGrade' => $produto['grade']));
                            //atualiza o MAPA_SEPARACAO_PRODUTO inserindo a nova quantidade somado ao que ja existia
                            $mapaSeparacaoProdutoEntity->setQtdSeparar(Math::adicionar($mapaSeparacaoProdutoEntity->getQtdSeparar(), $novaQuantidade));
                            $this->getEntityManager()->persist($mapaSeparacaoProdutoEntity);

                            //altero a nova quantidade para valor negativo por se tratar de saída de estoque
                            $novaQuantidade = $novaQuantidade * -1;
                            //localiza as reservas de estoque
                            $reservaEstoqueProdutoEntity = $reservaEstoqueProdutoRepository->getReservaEstoqueProduto($PedidoEntity->getId(), $produto['codProduto'], $produto['grade']);
                            if ($reservaEstoqueProdutoEntity != null) {
                                $reservaEstoqueProdutoEntity[0]->setQtd(Math::adicionar($reservaEstoqueProdutoEntity->getQtd(), $novaQuantidade));
                                $this->getEntityManager()->persist($reservaEstoqueProdutoEntity);
                            }
                        }
                    }
                }
                $this->getEntityManager()->flush();
            }
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }


    }

    public function getClienteByExpedicao($idExpedicao)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('pessoa.nome, nvl(pj.cnpj, pf.cpf) documento, pe.descricao, pe.bairro, pe.localidade, pe.cep, pe.numero, s.sigla')
//            ->select('e.id')
            ->from('wms:Expedicao','e')
            ->innerJoin('wms:Expedicao\Carga','c','WITH','c.codExpedicao = e.id')
            ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'p.codCarga = c.id')
            ->innerJoin('wms:Expedicao\PedidoEndereco', 'pe', 'WITH', 'pe.codPedido = p.id')
            ->innerJoin('wms:Pessoa', 'pessoa', 'WITH', 'pessoa.id = p.pessoa')
            ->leftJoin('wms:Pessoa\Juridica', 'pj', 'WITH', 'pj.id = pessoa.id')
            ->leftJoin('wms:Pessoa\Fisica', 'pf', 'WITH', 'pf.id = pessoa.id')
            ->innerJoin('wms:Util\Sigla', 's', 'WITH', 'pe.uf = s.id')
            ->where("e.id = $idExpedicao");

        return $sql->getQuery()->getResult();
    }

    public function getClienteByMapa($codMapaSeparacao)
    {

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('pes.nome, nvl(pj.cnpj, pf.cpf) documento, pe.descricao, pe.bairro, pe.localidade, pe.cep, pe.numero, s.sigla')
            ->from('wms:Expedicao\MapaSeparacao','ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto','msp','WITH','msp.mapaSeparacao = ms.id')
            ->innerJoin('wms:Expedicao\PedidoProduto','pp','WITH','msp.pedidoProduto = pp.id')
            ->innerJoin('wms:Expedicao\Pedido','p','WITH','pp.pedido = p.id')
            ->leftJoin('wms:Expedicao\PedidoEndereco','pe','WITH','pe.pedido = p.id')
            ->innerJoin('wms:Pessoa','pes','WITH','pes.id = p.pessoa')
            ->leftJoin('wms:Pessoa\Fisica','pf', 'WITH', 'pf.id = pes.id')
            ->leftJoin('wms:Pessoa\Juridica','pj', 'WITH','pj.id = pes.id')
            ->leftJoin('wms:Util\Sigla','s','WITH','s.id = pe.uf')
            ->where("ms.id = $codMapaSeparacao");

        return $sql->getQuery()->getResult();

    }

    public function getReservasSemPedidosByExpedicao ($idEdpexicao) {

        $sql = "SELECT * FROM RESERVA_ESTOQUE_EXPEDICAO 
                 WHERE COD_EXPEDICAO = $idEdpexicao
                   AND COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM PEDIDO)";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSeqRotaPracaByMapa($idMapa)
    {
        $sql = "SELECT DISTINCT NVL(R.NUM_SEQ, 0) SEQ_ROTA, NVL(PR.NUM_SEQ, 0) SEQ_PRACA
                FROM CLIENTE C
                INNER JOIN PEDIDO P ON C.COD_PESSOA = P.COD_PESSOA
                INNER JOIN PEDIDO_PRODUTO PP on P.COD_PEDIDO = PP.COD_PEDIDO
                INNER JOIN MAPA_SEPARACAO_PEDIDO MSP on PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN ROTA R ON R.COD_ROTA = C.COD_ROTA
                INNER JOIN PRACA PR ON C.COD_PRACA = PR.COD_PRACA
                WHERE MSP.COD_MAPA_SEPARACAO = $idMapa";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}