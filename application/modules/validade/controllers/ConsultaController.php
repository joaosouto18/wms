<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Validade\Form\Index as ValidadeForm;

class Validade_ConsultaController extends Action
{
    public function indexAction()
    {
        $form = new ValidadeForm();
        $this->view->form = $form;
    }
}