<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta,
    Wms\Module\Web\Page,
    Wms\Module\Expedicao\Report\Produtos,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Module\Expedicao\Printer\MapaSeparacao as MapaSeparacao;

class Expedicao_EtiquetaController  extends Action
{

    public function indexAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('id');
        $action         = $this->getRequest()->getParam('urlAction');
        $controller     = $this->getRequest()->getParam('urlController');
        $showCarga      = $this->getRequest()->getParam('sc');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $this->view->centraisEntrega = $ExpedicaoRepo->getCentralEntregaPedidos($idExpedicao, false);
        if ($showCarga) {
            $this->view->cargas          = $ExpedicaoRepo->getCodCargasExterno($idExpedicao);
        }
        $this->view->action          = $action;
        $this->view->controller      = $controller;
        $this->view->expedicao       = $idExpedicao;
    }

    public function imprimirAction()
    {
        $em = $this->getEntityManager();
        $arrayRepositorios = array(
            'expedicao'           => $em->getRepository('wms:Expedicao'),
            'filial'               => $em->getRepository('wms:Filial'),
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

        ini_set('max_execution_time', 3000);
        $idExpedicao    = $this->getRequest()->getParam('id');
        $central        = $this->getRequest()->getParam('central');
        $cargas         = $this->getRequest()->getParam('cargas');

        if (empty($idExpedicao) || empty($central)) {
            $this->_redirect('/');
        }

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');

        try {
            $this->getEntityManager()->beginTransaction();

            if (!isset($cargas)) {
                echo 'errorÉ Necessário informar uma carga';
                exit;
            }

            //verifica se vai utilizar Ressuprimento
            $filialEn = $this->getEntityManager()->getRepository('wms:Filial')->findOneBy(array('codExterno'=>$central));
            if ($filialEn->getIndUtilizaRessuprimento() == "S") {
                $pedidosSemOnda = $ExpedicaoRepo->getPedidoProdutoSemOnda($idExpedicao,$central);
                if (count($pedidosSemOnda)) {
                    echo 'errorExistem pedidos sem onda de ressuprimento gerada na expedição ' . $idExpedicao;
                    exit;
                }
            }

            if (count($ExpedicaoRepo->getProdutosSemDadosByExpedicao($idExpedicao)) > 0) {
                $link = '<a href="' . $this->view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Dados Logísticos</a>';
                echo 'errorExistem produtos sem definição de volume. Clique para exibir ' . $link;
                exit;
            } else {
                $this->gerarMapaEtiqueta($idExpedicao,$central,$cargas,$arrayRepositorios);
            }

            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
        exit;
    }

    public function listarMapasQuebraAjaxAction()
    {
        $idExpedicao = $this->_getParam('id',0);
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $this->view->mapasSeparacao = $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('codExpedicao' => $idExpedicao, 'codStatus' => \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO), array('id' => 'ASC'));

        /**@var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $this->view->etiquetasSeparacao = $etiquetaSeparacaoEn = $etiquetaSeparacaoRepo->getEtiquetaPendenteImpressao($idExpedicao);
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

        if ($tipo == "mapa") {
            if ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idMapa) > 0) {
                $mapa = new \Wms\Module\Expedicao\Printer\MapaSeparacao();
                $mapa->layoutMapa($idExpedicao,$this->getSystemParameterValue('MODELO_MAPA_SEPARACAO'), $idMapa, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
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
            } else {
                $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
            }
            $ExpedicaoEn = $ExpedicaoRepo->findOneBy(array('id'=>$idExpedicao));
            if ($Etiqueta->jaImpressas($ExpedicaoEn) == false) {
                $this->addFlashMessage('info', 'Todas as etiquetas já foram impressas');
                $this->_redirect('/expedicao');
            } else {
                $Etiqueta->imprimir(array('idExpedicao' =>$idExpedicao, 'central' => $central, 'idEtiquetaMae' => $idEtiquetaMae),$modelo);
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Etiquetas Impressas', $idExpedicao);
            }
        }
        if ($tipo == "reentrega") {
            $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
            if ($this->getRequest()->getParam('todas') == 'S') $status = null;

            if ($modelo == '1') {
                $Etiqueta = new Etiqueta();
            } else {
                $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
            }
            $Etiqueta->imprimirReentrega($idExpedicao, $status, $modelo);

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
            $ExpedicaoEntity = $ExpedicaoRepo->find($idExpedicao);
            if ($ExpedicaoEntity->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_INTEGRADO) {
                $statusEntity = $this->getEntityManager()->getReference('wms:Util\Sigla',\Wms\Domain\Entity\Expedicao::STATUS_EM_SEPARACAO );
                $ExpedicaoEntity->setStatus($statusEntity);
                $this->getEntityManager()->persist($ExpedicaoEntity);
                $this->getEntityManager()->flush();
            }
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

    public function reimprimirAction() 
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
        $LeituraColetor = new LeituraColetor();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas      = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, false);
        $this->view->etiquetas = $etiquetas;

        if ($request->isPost()) {

            $senhaDigitada    = $request->getParam('senhaConfirmacao');
            $senhaAutorizacao = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'SENHA_AUTORIZAR_DIVERGENCIA'));
            $senhaAutorizacao = $senhaAutorizacao->getValor();
            if ($senhaDigitada == $senhaAutorizacao) {
                $codBarra    = $request->getParam('codBarra');
                $codBarra    = $LeituraColetor->retiraDigitoIdentificador($codBarra);
                $motivo      = $request->getParam('motivo');
                if (!$codBarra || !$motivo) {
                    $this->addFlashMessage('error', 'É necessário preencher todos os campos');
                    $this->_redirect('/expedicao/etiqueta/reimprimir/id'.$idExpedicao);
                }
                $etiquetaEntity = $EtiquetaRepo->findOneBy(array('id' => $codBarra));
                if ($etiquetaEntity == null ) {
                    $this->addFlashMessage('error', "Etiqueta não $codBarra encontrada");
                    $this->_redirect('/expedicao/etiqueta/reimprimir/id/'.$idExpedicao);
                }

                $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');
                if ($modelo == '1') {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 40));
                }else {
                    $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
                }

                if ($Etiqueta->jaReimpressa($etiquetaEntity)) {
                    $this->addFlashMessage('info', 'Etiqueta não pode ser reimpressa mais de uma vez');
                    $this->_redirect('/expedicao');
                }
                $Etiqueta->reimprimir($etiquetaEntity, $motivo, $modelo);

                if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                } else {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                }
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Reimpressão da etiqueta:'.$codBarra, $idExpedicao, false, true, $codBarra, $codBarrasProdutos);
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
                $this->_redirect('/expedicao/etiqueta/reimprimir/id/'.$idExpedicao);
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
        $mapaSeparacao = $mapaRepo->getMapaSeparacaoByExpedicao($idExpedicao);
        $this->view->mapaSeparacao = $mapaSeparacao;
        $reimprimirTodos = $this->_getParam('btnReimpressao');
        $reimprimirByCodBarras = $this->_getParam('btnConfirmacao');

        $mapa = new MapaSeparacao;

        if (isset($reimprimirTodos) && $reimprimirTodos != null) {
            $mapa->layoutMapa($idExpedicao, $this->getSystemParameterValue('MODELO_MAPA_SEPARACAO'), null, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
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
            $mapa->layoutMapa($idExpedicao, $this->getSystemParameterValue('MODELO_MAPA_SEPARACAO'), $codBarra, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
            $andamentoRepo->save('Reimpressão do Mapa:'.$codBarra, $idExpedicao, false, true, $codBarra);
        }
    }

    /**
     * @param $idExpedicao
     */
    protected function gerarMapaEtiqueta($idExpedicao, $central, $cargas, $arrayRepositorios) {

        try {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
            $EtiquetaRepo = $arrayRepositorios['etiquetaSeparacao'];

            if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {
                $EtiquetaRepo->gerarMapaEtiquetaReentrega($idExpedicao,$arrayRepositorios);
            }

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $arrayRepositorios['expedicao'];
            $pedidosProdutos = $ExpedicaoRepo->findPedidosProdutosSemEtiquetaById($idExpedicao, $central, $cargas);

            $idModeloSeparacaoPadrao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

            if (count($pedidosProdutos) == 0) {
                if (($ExpedicaoRepo->getQtdEtiquetasPendentesImpressao($idExpedicao) <= 0)
                     && ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idExpedicao)  <= 0))  {
                    $cargas = implode(',',$cargas);
                    echo 'errorEtiquetas não existem ou já foram geradas na expedição:'.$idExpedicao.' central:'.$central.' com a[s] cargas:'.$cargas;
                    exit;
//                    $this->addFlashMessage('error', 'Etiquetas não existem ou já foram geradas na expedição:'.$idExpedicao.' central:'.$central.' com a[s] cargas:'.$cargas );
                }
            } else {
                $EtiquetaRepo->gerarMapaEtiqueta($idExpedicao, $pedidosProdutos,null,$idModeloSeparacaoPadrao, $arrayRepositorios);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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

    public function reimprimirEmbaladosAction()
    {
        $idExpedicao = $this->_getParam('id');
        $existeItensPendentes = true;

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');

        $etiqueta = $mapaSeparacaoEmbaladoRepo->getDadosEmbalado(null,$idExpedicao);
        if (!isset($etiqueta) || empty($etiqueta) || count($etiqueta) <= 0) {
            throw new \Exception(utf8_encode('Não existe produtos conferidos para esse volume embalado!'));
        }
        $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(110, 50));
        $gerarEtiqueta->imprimirExpedicaoModelo1($etiqueta,$existeItensPendentes);
    }
}