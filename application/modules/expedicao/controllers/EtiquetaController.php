<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta,
    Wms\Module\Web\Page,
    Wms\Module\Expedicao\Report\Produtos,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Module\Expedicao\Printer\MapaSeparacao as MapaSeparacao,
    Wms\Module\Expedicao\Report\ProdutosSemEtiquetas as ProdutosSemEtiquetas;
;

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
        ini_set('max_execution_time', 3000);
        $idExpedicao    = $this->getRequest()->getParam('id');
        $central        = $this->getRequest()->getParam('central');
        $cargas         = $this->getRequest()->getParam('cargas');

        if (empty($idExpedicao) || empty($central)) {
            $this->_redirect('/');
        }

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');

        //verifica se vai utilizar Ressuprimento
        $filialEn = $this->getEntityManager()->getRepository('wms:Filial')->findOneBy(array('codExterno'=>$central));
        if ($filialEn->getIndUtilizaRessuprimento() == "S") {
            $pedidosSemOnda = $ExpedicaoRepo->getPedidoProdutoSemOnda($idExpedicao,$central);
            if (count($pedidosSemOnda)) {
                $mensagem = 'Existem pedidos sem onda de ressuprimento gerada na expedição ' . $idExpedicao;
                $this->addFlashMessage('error', $mensagem );
                $this->_redirect('/expedicao');
            }
        }

        if (count($ExpedicaoRepo->getProdutosSemDadosByExpedicao($idExpedicao)) > 0) {
            $link = '<a href="' . $this->view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Dados Logísticos</a>';
            $mensagem = 'Existem produtos sem definição de volume. Clique para exibir ' . $link;

            $this->addFlashMessage('error', $mensagem );
            $this->_redirect('/expedicao');
        } else {
            $this->gerarMapaEtiqueta($idExpedicao,$central,$cargas);
        }

        $linkEtiqueta = "";
        $linkMapa = "";
        if ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idExpedicao) > 0)
            $linkMapa     = '<a href="' . $this->view->url(array('controller' => 'etiqueta', 'action' => 'gerar-pdf-ajax', 'id' => $idExpedicao, 'tipo'=>'mapa', 'central' => $central)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Mapa de Separação</a>';
        if ($ExpedicaoRepo->getQtdEtiquetasPendentesImpressao($idExpedicao) > 0)
            $linkEtiqueta = '<a href="' . $this->view->url(array('controller' => 'etiqueta', 'action' => 'gerar-pdf-ajax', 'id' => $idExpedicao, 'tipo'=>'etiqueta', 'central'=>$central)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Etiqueta de Separação</a>';

            if (($linkMapa != "") &&($linkEtiqueta != "")) {
                $mensagem = "Clique para imprimir " . $linkMapa . " - " . $linkEtiqueta;
            } else {
                $mensagem = "Clique para imprimir " . $linkMapa . $linkEtiqueta;
            }

        $this->addFlashMessage('success', $mensagem );
        $this->_redirect('/expedicao');

    }

    public function gerarPdfAjaxAction(){
        $central        = $this->getRequest()->getParam('central');
        $idExpedicao    = $this->getRequest()->getParam('id');
        $tipo    = $this->getRequest()->getParam('tipo');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');

        if ($tipo == "mapa") {
            if ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idExpedicao) > 0) {
                $mapa = new \Wms\Module\Expedicao\Printer\MapaSeparacao();
                $mapa->imprimir($idExpedicao);
            } else {
                $this->addFlashMessage('info', 'Todos os mapas ja foram impressos');
                $this->_redirect('/expedicao');
            }

        }
        if ($tipo == "etiqueta") {
            $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');
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
                $Etiqueta->imprimir(array('idExpedicao' =>$idExpedicao, 'central' => $central),$modelo);
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Etiquetas Impressas', $idExpedicao);
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
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Reimpressão da etiqueta:'.$codBarra, $idExpedicao);

            }else {
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
        $LeituraColetor = new LeituraColetor();

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');
        $mapaSeparacao = $mapaRepo->getMapaSeparacaoByExpedicao($idExpedicao);
        $this->view->mapaSeparacao = $mapaSeparacao;
        $reimprimirTodos = $this->_getParam('btnReimpressao');
        $reimprimirByCodBarras = $this->_getParam('btnConfirmacao');

        $mapa = new MapaSeparacao;

        if (isset($reimprimirTodos) && $reimprimirTodos != null) {
            $mapa->imprimir($idExpedicao);
        } elseif (isset($reimprimirByCodBarras) && $reimprimirByCodBarras != null) {
            $codBarra    = $request->getParam('codBarra');
            $codBarra    = $LeituraColetor->retiraDigitoIdentificador($codBarra);
            if (!$codBarra) {
                $this->addFlashMessage('error', 'É necessário informar o Código de Barras');
                $this->_redirect('/expedicao/etiqueta/reimprimir-mapa/id'.$idExpedicao);
            }
            $mapaSeparacaoEntity = $mapaRepo->findOneBy(array('id' => $codBarra));
            if ($mapaSeparacaoEntity == null ) {
                $this->addFlashMessage('error', "Etiqueta $codBarra não encontrada");
                $this->_redirect('/expedicao/etiqueta/reimprimir-mapa/id/'.$idExpedicao);
            }
            $mapa->reimprimirMapaByCodBarras($idExpedicao, $codBarra);

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
            $andamentoRepo->save('Reimpressão da etiqueta:'.$codBarra, $idExpedicao);
        }
    }

    /**
     * @param $idExpedicao
     */
    protected function gerarMapaEtiqueta($idExpedicao, $central, $cargas) {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $pedidosProdutos = $ExpedicaoRepo->findPedidosProdutosSemEtiquetaById($idExpedicao, $central, $cargas);

        if (count($pedidosProdutos) == 0) {
            if ($ExpedicaoRepo->getQtdEtiquetasPendentesImpressao($idExpedicao) > 0)  return;
            if ($ExpedicaoRepo->getQtdMapasPendentesImpressao($idExpedicao) > 0) return;
            $cargas = implode(',',$cargas);
            $this->addFlashMessage('error', 'Etiquetas não existem ou já foram geradas na expedição:'.$idExpedicao.' central:'.$central.' com a[s] cargas:'.$cargas );
        }

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        try {
            $EtiquetaRepo->gerarMapaEtiqueta($pedidosProdutos,null,1);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
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

}