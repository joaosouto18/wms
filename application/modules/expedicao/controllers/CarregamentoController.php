<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_CarregamentoController extends Action
{

    public function indexAction()
    {
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');
        $form = new \Wms\Module\Expedicao\Form\Carregamento();

        $request = $this->getRequest();
        $params = $request->getParams();

        $codExpedicao = null;
        if (isset($params['codExpedicao'])) {
            $codExpedicao = $params['codExpedicao'];
        }

        $codCarga = null;
        if (isset($params['codCarga'])) {
            $codCarga = $params['codCarga'];

            $cargaEn = $this->getEntityManager()->getRepository("wms:Expedicao\Carga")->findOneBy(array('codCargaExterno'=>$codCarga));
            if ($cargaEn != null) {
                $codExpedicao = $cargaEn->getExpedicao()->getId();
            }
        }

        $this->buttons($codExpedicao);

        if ($codExpedicao || $codCarga) {
            $form->populate($params);
            if (isset($params['pedido'])) {
                /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
                $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');

                if (isset($params['page'])) {
                    $page = $params['page'];
                } else {
                    $page = 1;
                }

                try {
                    $pedidoRepo->realizaSequenciamento($params['pedido']);
                    $this->addFlashMessage('success', 'Sequenciamento realizado com sucesso');
                    $this->_redirect('/expedicao/carregamento/index/page/'.$page.'?codExpedicao='.$codExpedicao);
                } catch (Expedicao $e) {
                    $this->addFlashMessage('error',  $e->getMessage());
                    $this->_redirect('/expedicao/carregamento');
                }

            } else {
                $grid = new \Wms\Module\Expedicao\Grid\Carregamento();
                $grid->init($params);
                $this->view->grid = $grid;
            }
        }
        $this->view->codExpedicao = $codExpedicao;
        $this->view->codCarga = $codCarga;
        $this->view->form = $form;
    }

    public function imprimirAjaxAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expRepo */
        $expRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $result = $expRepo->getCarregamentoByExpedicao($params['codExpedicao']);

        $imprimir = new \Wms\Module\Expedicao\Printer\Carregamento();
        $imprimir->imprimir($params['codExpedicao'],$result);

//        $this->exportPDF($result,'relatorio-sequenciamento','Imprimir','L');
    }

    public function buttons($codExpedicao)
    {
        if ($codExpedicao) {
            Page::configure(array(
                'buttons' => array(
//                    array(
//                        'label' => 'Imprimir relatório',
//                        'urlParams' => array(
//                            'module' => 'expedicao',
//                            'controller' => 'relatorio_carregamento',
//                            'action' => 'imprimir',
//                            'id' => $codExpedicao
//                        ),
//                        'tag' => 'a'
//                    ),
                    array(
                        'label' => 'Relatórios de Carregamentos',
                        'urlParams' => array(
                            'module' => 'expedicao',
                            'controller' => 'index',
                            'action' => 'relatorios-carregamento-ajax',
                            'id' => $codExpedicao
                        ),
                        'cssClass' => 'dialogAjax',
                        'tag' => 'a'
                    ),
                    array(
                        'label' => 'Imprimir Sequenciamento',
                        'urlParams' => array(
                            'module' => 'expedicao',
                            'controller' => 'carregamento',
                            'action' => 'imprimir-ajax',
                            'codExpedicao' => $codExpedicao
                        ),
                        'tag' => 'a'
                    )
                )
            ));
        }
    }

}