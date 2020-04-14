<?php


class Integracao_GerenciamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $grid = new \Wms\Module\Integracao\Grid\IntegracaoGrid();

        $this->view->grid = $grid->init();
    }
}