<?php

use Wms\Module\Web\Controller\Action\Crud;
use Wms\Module\Web\Grid\Equipamento as equipamentoGrid;
use Wms\Module\Web\Form\Equipamento\Equipamento as equipamentoForm;

class Web_EquipamentoController extends Crud
{
    protected $entityName = 'Equipamento';

    public function indexAction()
    {
        $form = new equipamentoForm();
        $values = $form->getParams();

        $this->view->form = $form;
        $grid = new equipamentoGrid();

        if (isset($values) && !empty($values)) {
            $this->view->grid = $grid->init($values)->render();
        } else {
            $this->view->grid = $grid->init()->render();
        }
    }

}
