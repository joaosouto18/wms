<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;

class Mobile_ReentregaController extends Action
{

    public function indexAction()
    {

    }

    public function recebimentoAction()
    {
        $this->view->form = new FormReentrega;

    }


}

