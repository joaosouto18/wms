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
            $numSequencial = $this->getMaxCodPedidoByCodExterno($pedido['codPedido'], true);
            $enPedido->setCodExterno($pedido['codPedido']);
            $enPedido->setTipoPedido($entitySigla);
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
            //regexp_replace(LPAD(PJ.NUM_CNPJ, 15, '0'),'([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})','\1.\2.\3/\4-\5') as CNPJ
        $controleProprietario = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'CONTROLE_PROPRIETARIO'))->getValor();
        if($controleProprietario == 'S'){
            $SQL = "SELECT EP.COD_PESSOA, (EP.QTD * -1) as ATENDIDA, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QUANTIDADE as QTD_PEDIDO, PJ.NUM_CNPJ as CNPJ
                    FROM PEDIDO_PRODUTO PP 
                    LEFT JOIN ESTOQUE_PROPRIETARIO EP ON (PP.COD_PRODUTO = EP.COD_PRODUTO AND PP.DSC_GRADE = EP.DSC_GRADE AND PP.COD_PEDIDO = EP.COD_OPERACAO)
                    LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                    WHERE PP.COD_PEDIDO = $codPedido";
        }else {
            $SQL = "SELECT PP.COD_PRODUTO, PP.DSC_GRADE, PP.QUANTIDADE as QTD_PEDIDO, PP.QUANTIDADE - NVL(PP.qtd_cortada,0) as ATENDIDA, '' AS CNPJ
                  FROM PEDIDO_PRODUTO PP WHERE PP.COD_PEDIDO = '$codPedido'";
        }
        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function finalizaPedidosByCentral ($PontoTransbordo, $Expedicao, $carga = null, $flush = true)
    {
        $query = "SELECT ped
                    FROM wms:Expedicao\Pedido ped
                   INNER JOIN ped.carga c
                   WHERE c.codExpedicao = $Expedicao
                     AND ped.pontoTransbordo = $PontoTransbordo";

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
     * @param $idPedido
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
     * @param $idPedido
     */
    public function cancelar($idPedido, $webService = true)
    {
        try {
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
     * @param $idPedido
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

        $EntPedido = $this->find($idPedido);
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
            $expedicaoAndamentoRepo->save("Pedido " . $idPedido . " cancelado da Expedição:" . $idExpedicao . ", Carga:" . $idCarga. " via integração", $idExpedicao, $idUsuario);
        } else {
            $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
            $expedicaoAndamentoRepo->save("Pedido " . $idPedido . " cancelado da Expedição:" . $idExpedicao . ", Carga:" . $idCarga. " manualmente ", $idExpedicao, $idUsuario);
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

        //APAGA MAPA_SEPARACAO_CONFERENCIA & MAPA_SEPARACAO_PRODUTO & MAPA_SEPARACAO_PEDIDO & MAPA_SEPARACAO_QUEBRA & MAPA_SEPARACAO_EMB_CLIENTE & MAPA_SEPARACAO CASO EXISTAM
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
        }

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
            foreach ($result as $item) {
                $entityPedido = $this->find($item->getId());
                $entityPedido->setSequencia($sequencia);
                $this->_em->persist($entityPedido);
            }
        }
       if ($this->_em->flush()) {
           return true;
       }
    }

    private function getPedidosByClienteExpedicao($codClientes,$codExpedicao)
    {
//        $clienteExternoArr = array();
//        foreach ($codClientes as $key => $codCliente) {
//            $clienteExternoArr[] = $key;
//        }
//        $codClienteExterno = implode(',',$clienteExternoArr);
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
                  WHERE P.COD_PEDIDO = '" . $codPedido . "'";

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getProdutosByPedido($codPedido){
        $SQL = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               PP.QUANTIDADE,
               NVL(PP.QTD_CORTADA,0) as QTD_CORTADA,
               (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_PESO,0) as NUM_PESO,
               (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_CUBAGEM,0) as NUM_CUBAGEM
          FROM PEDIDO_PRODUTO PP
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO AND PESO.DSC_GRADE = PP.DSC_GRADE
         WHERE PP.COD_PEDIDO = '$codPedido' ORDER BY COD_PRODUTO, DSC_GRADE";
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
         WHERE ES.COD_PEDIDO = '$codPedido' ORDER BY ES.COD_ETIQUETA_SEPARACAO";
        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function getPedidoByExpedicao($idExpedicao, $codProduto, $grade = 'UNICA')
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.codExterno as id, pe.nome cliente, NVL(i.descricao,\'PADRAO\') as itinerario, p.numSequencial')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('wms:Expedicao\PedidoProduto', 'pp', 'WITH', 'p.id = pp.codPedido')
            ->innerJoin('wms:Pessoa','pe', 'WITH', 'pe.id = p.pessoa')
            ->leftJoin('wms:Expedicao\Itinerario', 'i', 'WITH', 'i.id = p.itinerario')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where("e.id = $idExpedicao  and pp.quantidade > pp.qtdCortada")
            ->groupBy('p.codExterno, pe.nome, i.descricao, p.numSequencial')
            ->orderBy('pe.nome', 'asc');

        if (isset($codProduto) && !empty($codProduto)) {
            $sql->andWhere("pp.codProduto = '$codProduto' AND pp.grade = '$grade'");
        }

        $result = $sql->getQuery()->getResult();
        foreach ($result as $key => $value){
            if(!empty($value['numSequencial']) && $value['numSequencial'] > 1){
                $result[$key]['id'] = $value['id'].' - '.$value['numSequencial'];
            }
        }
        return $result;
    }

    public function getSituacaoPedido ($idPedido) {

        $sql = "SELECT DISTINCT
                    E.COD_EXPEDICAO
                FROM EXPEDICAO E
                INNER JOIN CARGA C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
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
                      OR (PP.COD_PEDIDO_PRODUTO NOT IN (SELECT COD_PEDIDO_PRODUTO FROM MAPA_SEPARACAO_PEDIDO) OR PP.COD_PEDIDO_PRODUTO NOT IN
                        (SELECT PP2.COD_PEDIDO_PRODUTO 
                         FROM ETIQUETA_SEPARACAO ES2 
                         INNER JOIN PEDIDO_PRODUTO PP2 ON PP2.COD_PEDIDO = ES2.COD_PEDIDO AND PP2.COD_PRODUTO = ES2.COD_PRODUTO AND PP2.DSC_GRADE = ES2.DSC_GRADE)))";

        $result = $this->_em->getConnection()->query($sql)->fetchAll();

        if (empty($result))
            return true;

        return false;
    }

    public function getMaxCodPedidoByCodExterno($idPedidoExterno, $numSequencial = false){
        $sql = "SELECT COD_PEDIDO, NUM_SEQUENCIAL FROM PEDIDO WHERE COD_EXTERNO = $idPedidoExterno ORDER BY NUM_SEQUENCIAL DESC ";
        $result = $this->_em->getConnection()->query($sql)->fetch();
        if($numSequencial == true){
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
                if($newProd['codProduto'] == $oldProd['COD_PRODUTO']){
                    return false;
                }
            }
        }
        return true;
    }

}