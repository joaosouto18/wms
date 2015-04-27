<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta,
    Wms\Module\Web\Page,
    Wms\Module\Expedicao\Report\Produtos,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Module\Expedicao\Report\ProdutosSemEtiquetas as ProdutosSemEtiquetas;
;

class Expedicao_EtiquetaController  extends Action
{

    public function indexAction()
    {

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $repository */
        $repository = $this->em->getRepository('wms:Expedicao\Pedido');
        $cancelar = $repository->cancelar(30994661);
        exit;


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
        $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');

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
            $this->gerarEtiquetas($idExpedicao,$central,$cargas);

            if ($modelo == '1') {
                $Etiqueta = new Etiqueta("L", 'mm', array(110, 40));
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
        //ini_set('max_execution_time', 30);
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

    /**
     * @param $idExpedicao
     */
    protected function gerarEtiquetas($idExpedicao, $central, $cargas) {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $pedidosProdutos = $ExpedicaoRepo->findPedidosProdutosSemEtiquetaById($idExpedicao, $central, $cargas);

        if (count($pedidosProdutos) == 0) {
            $cargas = implode(',',$cargas);
            $this->addFlashMessage('error', 'Pedidos não encontrados na expedição:'.$idExpedicao.' central:'.$central.' com a[s] cargas:'.$cargas );
            $this->_redirect('/expedicao');
        }

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($EtiquetaRepo->gerarEtiquetas($pedidosProdutos) > 0) {

            $link = '<a href="' . $this->view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Dados Logísticos</a>';
            $mensagem = 'Existem produtos sem definição de volume. Clique para exibir ' . $link;

            $this->addFlashMessage('error', $mensagem );
            $this->_redirect('/expedicao');
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