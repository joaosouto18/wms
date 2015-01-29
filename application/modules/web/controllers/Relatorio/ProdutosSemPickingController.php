<?php

use
    Wms\Module\Web\Page,
    Wms\Controller\Action,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Grid\ProdutoSemPicking as ProdutoSemPickingGrid,
    Wms\Module\Web\Form\Subform\FiltroProdutosSemPicking;

/**
 * Description of Web_Relatorio_ProdutosSemPickingController
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class Web_Relatorio_ProdutosSemPickingController extends Action
{


    /**
     *
     * @return type 
     */
    public function indexAction()
    {
        $form = new FiltroProdutosSemPicking;
        $this->view->form = $form;

        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        $form->populate($params);

        $Grid = new ProdutoSemPickingGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

        $this->view->refresh = true;
    }

}
