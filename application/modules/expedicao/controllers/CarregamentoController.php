<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_CarregamentoController  extends Action
{

    public function indexAction()
    {
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

    public function buttons($codExpedicao)
    {
        if ($codExpedicao) {
            Page::configure(array(
                'buttons' => array(
                    array(
                        'label' => 'Imprimir relatório',
                        'urlParams' => array(
                            'module' => 'expedicao',
                            'controller' => 'relatorio_carregamento',
                            'action' => 'imprimir',
                            'id' => $codExpedicao
                        ),
                        'tag' => 'a'
                    )
                )
            ));
        }
    }

}