<?php

use Wms\Domain\Entity\Expedicao\CaixaEmbalado;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository;
use Wms\Domain\Entity\Usuario;
use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta,
    Wms\Module\Web\Page,
    Wms\Module\Expedicao\Report\Produtos,
    Wms\Util\Coletor as ColetorUtil,
    Wms\Module\Expedicao\Printer\MapaSeparacao as MapaSeparacao;

class Expedicao_EtiquetaController  extends Action
{

    public function indexAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('id');
        $showCarga      = $this->getRequest()->getParam('sc');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $this->view->centraisEntrega = $ExpedicaoRepo->getCentralEntregaPedidos($idExpedicao, false);
        if ($showCarga) {
            $this->view->cargas          = $ExpedicaoRepo->getCodCargasExterno($idExpedicao);
        }
        $expedicaoEntity = $ExpedicaoRepo->findOneBy(array('id' => $idExpedicao));

        $this->view->expedicao = $expedicaoEntity;
        $this->view->boxes     = $this->_em->getRepository('wms:Deposito\Box')->findAll();
    }

    public function imprimirAction()
    {
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

        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '-1');
        $idExpedicao    = $this->getRequest()->getParam('id');
        $central        = $this->getRequest()->getParam('central');
        $cargas         = $this->getRequest()->getParam('cargas');

        if (empty($idExpedicao) || empty($central)) {
            $this->_redirect('/');
        }

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $hasTransaction = false;
        $updateStatus = false;
        try {
            $expedicaoEn = $ExpedicaoRepo->find($idExpedicao);
            if ($expedicaoEn == null) throw new \Exception("Expedição $idExpedicao não encontrada");

            if ($expedicaoEn->getIndProcessando() == 'S') throw new \Wms\Util\WMS_Exception("A Expedição $idExpedicao se encontra em processamento por outro usuário");

            $ExpedicaoRepo->changeSituacaoExpedicao($idExpedicao, 'S');
            $updateStatus = true;

            $this->getEntityManager()->beginTransaction();
            $hasTransaction = true;

            if (!isset($cargas)) {
                $msg = "É Necessário informar uma carga";
                throw new \Wms\Util\WMS_Exception($msg);
            }

            //verifica se vai utilizar Ressuprimento
            $filialEn = $this->getEntityManager()->getRepository('wms:Filial')->findOneBy(array('codExterno'=>$central));
            if ($filialEn->getIndUtilizaRessuprimento() == "S") {
                $pedidosSemOnda = $ExpedicaoRepo->getPedidoProdutoSemOnda($idExpedicao,$central);
                if (count($pedidosSemOnda)) {
                    $msg = "Existem pedidos sem onda de ressuprimento gerada na expedição $idExpedicao";
                    throw new \Wms\Util\WMS_Exception($msg);
                }
            }

            if (count($ExpedicaoRepo->getProdutosSemDadosByExpedicao($idExpedicao)) > 0) {
                $link = $this->view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao));
                $msg = "Existem produtos sem definição de volume. Deseja exibir?";
                throw new \Wms\Util\WMS_Exception($msg, $link);
            } else {
                $this->gerarMapaEtiqueta($idExpedicao,$central,$cargas,$arrayRepositorios);
            }

            //GERA ETIQUETA MAPA ERP
            if ($this->getSystemParameterValue('IND_INFORMA_ERP_ETQ_MAPAS_IMPRESSOS_INTEGRACAO') == 'S' ) {
                $idIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_INFORMA_ERP_ETQ_MAPAS_IMPRESSOS');

                /** @var Usuario $usuario */
                $usuario = $this->em->find('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
                $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

                $codIntegracoes = explode(',',$idIntegracao);

                foreach ($codIntegracoes as $codIntegracao) {

                    $acaoEn = $acaoIntRepo->find($codIntegracao);
                    if ($ExpedicaoRepo->verificaViabilidadeIntegracaoExpedicao($expedicaoEn, $acaoEn) == true) {

                        $options = array();

                        if(!is_null($cargas) && is_array($cargas)) {
                            $options[] = implode(',',$cargas);
                            $options[] = $usuario->getCodErp();
                        } else if (!is_null($cargas)) {
                            $options = $cargas;
                        }

                        $result = $acaoIntRepo->processaAcao($acaoEn,$options,'E',"P",null,612);
                        if (!$result === true) {
                            throw new \Wms\Util\WMS_Exception($result);
                        }
                    }
                }
            }

            $this->getEntityManager()->commit();
            $ExpedicaoRepo->changeSituacaoExpedicao($idExpedicao, 'N');

            $this->_helper->json(array('status' => 'success'));
        } catch (\Wms\Util\WMS_Exception $e) {
            if ($hasTransaction) $this->getEntityManager()->rollback();
            if ($updateStatus) $ExpedicaoRepo->changeSituacaoExpedicao($idExpedicao, 'N');

            $this->_helper->json(array('status' => 'error', 'msg' => $e->getMessage(), 'link' => $e->getLink()));
        } catch (\Exception $e) {
            if ($hasTransaction) $this->getEntityManager()->rollback();
            if ($updateStatus) $ExpedicaoRepo->changeSituacaoExpedicao($idExpedicao, 'N');

            $this->_helper->json(array('status' => 'error', 'msg' => $e->getMessage(), 'link' => ''));
        }


    }

    public function listarMapasQuebraAjaxAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $idExpedicao = $this->_getParam('id',0);
        $return = $this->_getParam('return', 'view');

        $this->view->box = $this->_getParam('box');
        $this->view->idExpedicao = $idExpedicao;
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $mapas = $mapaSeparacaoRepo->findBy(array('codExpedicao' => $idExpedicao, 'codStatus' => \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO), array('id' => 'ASC'));

        /**@var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas = $etiquetaSeparacaoRepo->getEtiquetaPendenteImpressao($idExpedicao);

        $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
        $pendencias = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, $status);
        if (count($pendencias) >0) {
            $temReentrega = 'S';
        } else {
            $temReentrega = 'N';
        }

        if ($return == "view") {
            $this->view->mapasSeparacao = $mapas;
            $this->view->etiquetasSeparacao = $etiquetas;
            $this->view->reentrega = $temReentrega;
        } else {
            $idsMapas = [];
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacao $mapa */
            foreach ($mapas as $mapa) {
                $idsMapas[] = $mapa->getId();
            }
            $idsEtiquetas = [];
            foreach($etiquetas as $etiquetaSeparacao) {
                $idsEtiquetas[] = $etiquetaSeparacao['id'];
            }
            $this->_helper->json(["mapas" => $idsMapas, "etiquetas" => $idsEtiquetas, "temReentrega", $temReentrega]);
        }
    }

    public function etiquetaCargaAjaxAction(){
        $codCargaExterno    = $this->getRequest()->getParam('carga');
        $idExpedicao        = $this->getRequest()->getParam('id');

        $pdf = new \Wms\Module\Expedicao\Printer\IdentificacaoCarga("L");
        $pdf->imprimir($idExpedicao,$codCargaExterno);

    }

    public function gerarPdfAjaxAction(){
        $tipo              = $this->getRequest()->getParam('tipo');
        $idMapa            = $this->getRequest()->getParam('idMapa');
        $central           = $this->getRequest()->getParam('central');
        $idEtiquetaMae     = $this->getRequest()->getParam('idEtiqueta');
        $conf              = $this->getRequest()->getParam('conf');
        $idBox             = $this->getRequest()->getParam('box');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $etiquetaMaeRepo = $this->em->getRepository('wms:Expedicao\EtiquetaMae');
        $mapaSeparacaoRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacao');
        if (isset($idMapa) && !empty($idMapa)) {
            $arrMapa        = explode(',',$idMapa);
            $mapaSeparacaoEn = $mapaSeparacaoRepo->find($arrMapa[0]);
            $idExpedicao = $mapaSeparacaoEn->getCodExpedicao();
        } elseif (isset($idEtiquetaMae) && !empty($idEtiquetaMae)) {
            $arrEtiquetaMae = explode(',',$idEtiquetaMae);
            $etiquetaMaeEn = $etiquetaMaeRepo->find($arrEtiquetaMae[0]);
            $idExpedicao = $etiquetaMaeEn->getCodExpedicao();
        }
        $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');
        try {
            if ($tipo == "mapa") {
                if ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idMapa) > 0) {
                    $mapa = new \Wms\Module\Expedicao\Printer\MapaSeparacao();
                    if ($conf == 1) {
                        $modelo = 6;
                    } else {
                        $modelo = $this->getSystemParameterValue('MODELO_MAPA_SEPARACAO');
                    }

                    $mapa->layoutMapa($idExpedicao, $modelo, $idMapa, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $idBox);
                    /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                    $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
                    $andamentoRepo->save('Mapas Impressos', $idExpedicao);
                } else {
                    $this->addFlashMessage('info', "Mapas já impressos!");
                    $this->_redirect('/expedicao');
                }
            }
            if ($tipo == "etiqueta") {

                /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
                $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');

                if ($modelo == '1') {
                    $Etiqueta = new Etiqueta();
                } elseif ($modelo == '10') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(100, 60));
                } elseif ($modelo == '14' || $modelo == '16') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 75));
                } elseif ($modelo == '17') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 55));
                } elseif ($modelo == '18') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(100, 35));
                } else {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
                }
                $ExpedicaoEn = $ExpedicaoRepo->findOneBy(array('id' => $idExpedicao));
                if ($Etiqueta->jaImpressas($ExpedicaoEn) == false) {
                    $this->addFlashMessage('info', 'Todas as etiquetas já foram impressas');
                    $this->_redirect('/expedicao');
                } else {
                    $Etiqueta->imprimir(array('idExpedicao' => $idExpedicao, 'central' => $central, 'idEtiquetaMae' => $idEtiquetaMae), $modelo, $idBox);
                    /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                    $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
                    $andamentoRepo->save('Etiquetas Impressas', $idExpedicao);
                }
            }
            if ($tipo == "reentrega") {
                $idExpedicao = $this->getRequest()->getParam('idExpedicao');

                $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
                if ($this->getRequest()->getParam('todas') == 'S') $status = null;

                if ($modelo == '1') {
                    $Etiqueta = new Etiqueta();
                } elseif ($modelo == '10') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(100, 60));
                } else {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
                }
                $Etiqueta->imprimirReentrega($idExpedicao, $status, $modelo);
            }

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
            $ExpedicaoEntity = $ExpedicaoRepo->find($idExpedicao);
            if ($ExpedicaoEntity->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_INTEGRADO) {

                $statusEntity = $this->getEntityManager()->getReference('wms:Util\Sigla', \Wms\Domain\Entity\Expedicao::STATUS_EM_SEPARACAO);
                $ExpedicaoEntity->setStatus($statusEntity);

                if (!is_null($idBox)) {
                    $boxEntity = $this->getEntityManager()->getReference('wms:Deposito\Box', $idBox);
                    $ExpedicaoEntity->setBox($boxEntity);
                }

                $this->getEntityManager()->persist($ExpedicaoEntity);
            }

            $this->getEntityManager()->flush();
            if ($tipo == "reentrega") {
                exit;
            }
        } catch (Exception $e) {
            $this->addFlashMessage("error", "Erro na geração do PDF: " . $e->getMessage());
            $this->_redirect('/expedicao');
        }
    }

    public function semDadosAction() 
    {
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('id');
        $central        = $this->getRequest()->getParam('central');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');

        $Relatorio = new Produtos("L","mm","A4");
        $Relatorio->imprimirSemDados($idExpedicao, $ExpedicaoRepo->findProdutosSemEtiquetasById($idExpedicao, $central),$central);

    }

    public function verificarReimpressaoAjaxAction () {
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('id');
        $this->view->idExpedicao = $idExpedicao;

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $etqReentrega = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao);
        $etqSeparacao = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, false);

        if (count($etqSeparacao) >0) {
            $this->view->existeSeparacao = 'S';
        } else {
            $this->view->existeSeparacao = 'N';
        }

        if (count($etqReentrega) >0) {
            $this->view->existeReentrega = 'S';
        } else {
            $this->view->existeReentrega = 'N';
        }

    }

    public function  reimprimirAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Expedições',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'module' => 'expedicao',
                        'controller' => 'index',
                        'action' => 'index'
                    )
                ),
            )
        ));
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('id');
        $complementoUrl = '';
        $reentrega   = $request->getParam('reentrega','N');
        if ($reentrega == 'S') {$complementoUrl = '/reentrega/S';}
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($reentrega == 'S') {
            $reentregas = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao);
            $etiquetas = array();
            foreach ($reentregas as $etqReentrega) {
                $etiquetas[] = array(
                    'codBarras' => $etqReentrega['ETIQUETA'],
                    'codProduto' => $etqReentrega['COD_PRODUTO'],
                    'produto' => $etqReentrega['PRODUTO'],
                    'grade' => $etqReentrega['DSC_GRADE'],
                    'pedido' => $etqReentrega['PEDIDO'],
                    'cliente' => $etqReentrega['CLIENTE']
                );
            }
        } else {
            $etiquetas = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, false);
        }

        $this->view->etiquetas = $etiquetas;

        if ($request->isPost()) {

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

            $senhaDigitada    = $request->getParam('senhaConfirmacao');
            $senhaAutorizacao = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'SENHA_AUTORIZAR_DIVERGENCIA'));
            $senhaAutorizacao = $senhaAutorizacao->getValor();
            if ($senhaDigitada == $senhaAutorizacao) {
                $codBarra    = $request->getParam('codBarra');
                $codBarra    = ColetorUtil::retiraDigitoIdentificador($codBarra);
                $motivo      = $request->getParam('motivo');
                $etiqueta = $request->getParam('etiqueta');

                if (!$motivo) {
                    $this->addFlashMessage('error', 'É necessário preencher todos os campos');
                    $this->_redirect('/expedicao/etiqueta/reimprimir' . $complementoUrl . '/id'.$idExpedicao);
                }

                $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');
                if ($modelo == '1') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 40));
                } elseif ($modelo == '10') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(100, 60));
                } elseif ($modelo == '16') {
                    $Etiqueta = new Etiqueta('L', 'mm',array(100,75));
                } elseif ($modelo == '18') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(100, 35));
                } else {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
                }

                if ($reentrega == "N") {

                    $arrEtiquetasEn = array();
                    foreach($etiqueta as $etq) {
                        $etiquetaEntity = $EtiquetaRepo->findOneBy(array('id' => $etq));
                        if ($etiquetaEntity == null ) {
                            $this->addFlashMessage('error', "Etiqueta não $codBarra encontrada");
                            $this->_redirect('/expedicao/etiqueta/reimprimir' . $complementoUrl . '/id/'.$idExpedicao);
                        }

                        if ($etiquetaEntity->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO) {
                            $this->addFlashMessage('info', "Etiqueta $etq ja está cortada e não pode ser reimpressa");
                            $this->_redirect('/expedicao/etiqueta/reimprimir' . $complementoUrl . '/id/'.$idExpedicao);
                        }

                        if ($Etiqueta->jaReimpressa($etiquetaEntity)) {
                            $this->addFlashMessage('info', "Etiqueta $etq não pode ser reimpressa mais de uma vez");
                            $this->_redirect('/expedicao/etiqueta/reimprimir' . $complementoUrl . '/id/'.$idExpedicao);
                        }

                        if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                            $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                        } else {
                            $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                        }

                        $andamentoRepo->save('Reimpressão da etiqueta:'.$codBarra, $idExpedicao, false, false, $etq, $codBarrasProdutos);

                        $arrEtiquetasEn[] = $etiquetaEntity;
                    }
                    $Etiqueta->reimprimir($arrEtiquetasEn, $motivo, $modelo, $idExpedicao);
                } else {
                    $Etiqueta->imprimirReentrega($idExpedicao, null, $modelo,true,$etiqueta);

                    foreach ($etiqueta as $etq) {
                        $andamentoRepo->save('Reimpressão da etiqueta de reentrega:'.$etq, $idExpedicao, false, false, $etq, null);
                    }

                }

                $this->getEntityManager()->flush();

                if ($reentrega == "S") {
                    exit;
                }

            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
                $this->_redirect('/expedicao/etiqueta/reimprimir' . $complementoUrl . '/id/'.$idExpedicao);
            }

        }

    }

    public function reimprimirMapaAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Expedições',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'module' => 'expedicao',
                        'controller' => 'index',
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                ),
            )
        ));
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('id');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');

        $mapaSeparacao = $mapaRepo->getMapaSeparacaoByExpedicao($idExpedicao);
        $this->view->mapaSeparacao = $mapaSeparacao;
        $reimprimirTodos = $this->_getParam('btnReimpressao');
        $reimprimirByCodBarras = $this->_getParam('btnConfirmacao');
        $reimprimirConf = $this->_getParam('btnReimpressaoConf');

        $expedicaoEn = $expedicaoRepo->find($idExpedicao);
        $idBox = null;
        if ($expedicaoEn->getBox() != null){
            $boxEn = $expedicaoEn->getBox();
            $idBox = $boxEn->getId();
        }

        $mapa = new MapaSeparacao;

        if (isset($reimprimirTodos) && $reimprimirTodos != null) {
            $arrStatus = [
                \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA,
                \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CONFERIDO
            ];
            $mapa->layoutMapa($idExpedicao, $this->getSystemParameterValue('MODELO_MAPA_SEPARACAO'), null, $arrStatus, $idBox);
        } elseif (isset($reimprimirByCodBarras) && $reimprimirByCodBarras != null) {
            $codBarra    = $request->getParam('codBarra');
            if (!$codBarra) {
                $this->addFlashMessage('error', 'É necessário informar o Código de Barras');
                $this->_redirect('/expedicao/etiqueta/reimprimir-mapa/id/'.$idExpedicao);
            }
            $mapaSeparacaoEntity = $mapaRepo->findOneBy(array('id' => $codBarra));
            if ($mapaSeparacaoEntity == null ) {
                $this->addFlashMessage('error', "Mapa $codBarra não encontrado");
                $this->_redirect('/expedicao/etiqueta/reimprimir-mapa/id/'.$idExpedicao);
            }
            $mapa->layoutMapa($idExpedicao, $this->getSystemParameterValue('MODELO_MAPA_SEPARACAO'), $codBarra, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA, $idBox);

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
            $andamentoRepo->save('Reimpressão do Mapa:'.$codBarra, $idExpedicao, false, true, $codBarra);
        }elseif (isset($reimprimirConf) && $reimprimirConf != null) {
            $mapa->layoutMapa($idExpedicao, 6, null, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA, $idBox);
        }
    }

    /**
     * @param $idExpedicao
     * @param $central
     * @param $cargas
     * @param $arrayRepositorios
     * @throws Exception
     * @throws \Wms\Util\WMS_Exception
     */
    protected function gerarMapaEtiqueta($idExpedicao, $central, $cargas, $arrayRepositorios) {

        try {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
            $EtiquetaRepo = $arrayRepositorios['etiquetaSeparacao'];

            if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {
                $EtiquetaRepo->gerarMapaEtiquetaReentrega($idExpedicao, $arrayRepositorios);
            }

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $arrayRepositorios['expedicao'];
            $pedidosProdutos = $ExpedicaoRepo->findPedidosProdutosSemEtiquetaById($central, $cargas);

            $idModeloSeparacaoPadrao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

            $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
            $qtdReentregaPendente = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, $status);

            if (empty($pedidosProdutos)) {
                if (($ExpedicaoRepo->getQtdEtiquetasPendentesImpressao($idExpedicao) <= 0)
                    && ($ExpedicaoRepo->getQtdMapasPendentesImpressaoByExpedicao($idExpedicao) <= 0)
                    && (count($qtdReentregaPendente) <= 0))
                {
                    $cargas = implode(',', $cargas);
                    $msg = "Etiquetas não existem ou já foram geradas na expedição: $idExpedicao central: $central com a(s) carga(s): $cargas";
                    throw new \Wms\Util\WMS_Exception($msg);
                }
            } else {
                $EtiquetaRepo->gerarMapaEtiqueta($idExpedicao, $pedidosProdutos, null, $idModeloSeparacaoPadrao, $arrayRepositorios);
            }
        } catch (\Wms\Util\WMS_Exception $WMS_Exception) {
            throw $WMS_Exception;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function consultarAction()
    {
        $form = new \Wms\Module\Expedicao\Form\ConsultaEtiqueta();
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['submit']);

        $form->populate($params);
        $this->view->form = $form;

        $Grid = new \Wms\Module\Web\Grid\ConsultaEtiqueta();
        $this->view->grid = $Grid->init($params)->render();
    }

    public function dadosEtiquetaAction()
    {
        $params = $this->_getAllParams();
        $idEtiqueta = $params['id'];
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['submit']);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaSeparacaoRepo->getDadosEtiquetaByEtiquetaId($idEtiqueta);

        $this->view->expedicoes = $result;
    }

    public function getIntervaloAjaxAction()
    {
        $primeira = $this->_getParam('primeira');
        $ultima = $this->_getParam('ultima');

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaSeparacaoRepo->getEtiquetasByFaixa($primeira, $ultima, true);
        $this->_helper->json(array('result'=>count($result)));
    }

    public function reimprimirEmbaladoUnicoAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);

        $idExpedicao = $this->_getParam('id');
        $idMapaEmbalado = $this->_getParam('COD_MAPA_SEPARACAO_EMB_CLIENTE');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');

        try {
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->find($idMapaEmbalado);

            /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->getModeloSeparacao($idExpedicao);
            $fechaEmbaladosNoFinal = ($modeloSeparacaoEn->getCriarVolsFinalCheckout() == 'S');

            $etiqueta = $mapaSeparacaoEmbaladoRepo->getDadosEmbalado($mapaSeparacaoEmbaladoEn->getId());
            if (empty($etiqueta)) {
                $this->addFlashMessage('error', 'Não existe volume embalado para ser reimpresso!');
                $this->_redirect('/expedicao/index');
            }
            $modeloEtiqueta = $this->getSystemParameterValue('MODELO_VOLUME_EMBALADO');
            $xy = explode(",",$this->getSystemParameterValue('TAMANHO_ETIQUETA_VOLUME_EMBALADO'));



            $produtosByVolume = $mapaSeparacaoEmbaladoRepo->getQtdProdByVol($mapaSeparacaoEmbaladoEn->getId());
            $qtdProdutosByVolume = reset($produtosByVolume)['QTD_PRODUTOS'];

            switch ($modeloEtiqueta) {
                case 1:
                    //LAYOUT CASA DO CONFEITEIRO
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                    break;
                case 2:
                    //LAYOUT WILSO
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 3:
                    //LAYOUT ABRAFER ...
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 4:
                    //LAYOUT HIDRAU
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 5:
                    //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 6:
                    //LAYOUT PLANETA
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 7:
                    //LAYOUT MBLED
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 8:
                    //LAYOUT PREMIUM
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(110, 50));
                    break;
                case 9:
                    //LAYOUT VETSS
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 10:
                    //LAYOUT MOTOARTE
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                default:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                    break;

            }

            $gerarEtiqueta->imprimirExpedicaoModelo($etiqueta,$mapaSeparacaoEmbaladoRepo,$modeloEtiqueta, $fechaEmbaladosNoFinal);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function reimprimirEmbaladosAction()
    {
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->getModeloSeparacao($idExpedicao);
        $fechaEmbaladosNoFinal = ($modeloSeparacaoEn->getCriarVolsFinalCheckout() == 'S');
        try {
            $etiqueta = $mapaSeparacaoEmbaladoRepo->getDadosEmbalado(null,$idExpedicao);
            if (empty($etiqueta)) {
                $this->addFlashMessage('error', 'Não existe volume embalado para ser reimpresso!');
                $this->_redirect('/expedicao/index');
            }
            $modeloEtiqueta = $this->getSystemParameterValue('MODELO_VOLUME_EMBALADO');
            $xy = explode(",",$this->getSystemParameterValue('TAMANHO_ETIQUETA_VOLUME_EMBALADO'));

            switch ($modeloEtiqueta) {
                case 1:
                    //LAYOUT CASA DO CONFEITEIRO
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                    break;
                case 2:
                    //LAYOUT WILSO
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 3:
                    //LAYOUT ABRAFER ...
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 4:
                    //LAYOUT HIDRAU
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                    break;
                case 5:
                    //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 6:
                    //LAYOUT PLANETA
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 7:
                    //LAYOUT MBLED
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 8:
                    //LAYOUT PREMIUM
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(110, 50));
                    break;
                case 9:
                    //LAYOUT VETSS
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 10:
                    //LAYOUT MOTOARTE
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                case 11:
                    //LAYOUT MACROLUB
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                    break;
                default:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                    break;

            }

            $gerarEtiqueta->imprimirExpedicaoModelo($etiqueta,$mapaSeparacaoEmbaladoRepo,$modeloEtiqueta, $fechaEmbaladosNoFinal);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    public function gerarEtiquetaProdutoAjaxAction()
    {
        $idExpedicao = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepository */
        $pedidoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto');
        $pedidoProdutos = $pedidoProdutoRepository->getPedidoProdutoByExpedicao($idExpedicao);

        $arrayProduto = array();
        foreach ($pedidoProdutos as $key => $pedidoProduto) {
            $arrayProduto[$key]['codProduto'] = $pedidoProduto['COD_PRODUTO'];
            $arrayProduto[$key]['grade']      = $pedidoProduto['DSC_GRADE'];
            $arrayProduto[$key]['qtdItem']    = $pedidoProduto['QUANTIDADE'];


        }

        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO");
        $gerarEtiqueta = null;
        switch ($modelo) {
            case 1:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
                break;
            case 2:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
                break;
            case 3:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(75, 45));
                break;
            case 4:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(113, 70));
                break;
            case 5:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(60, 60));
                break;
        }

        $gerarEtiqueta->etiquetaProdutosExpedicao(array( 'produtos' => $arrayProduto), $modelo,\Wms\Domain\Entity\Recebimento::TARGET_IMPRESSAO_PRODUTO);

    }
}