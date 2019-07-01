<?php

use Wms\Module\Web\Controller\Action\Crud;

class Web_CaixaEmbaladoController extends Crud
{

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        $this->entityName = "Expedicao\CaixaEmbalado";
        parent::__construct($request, $response, $invokeArgs);
    }

    public function indexAction()
    {
        $grid = new \Wms\Module\Web\Grid\Expedicao\CaixaEmbalado();
        $this->view->grid = $grid->init();
    }

    public function addAction()
    {
        parent::addAction();
        $this->renderScript("caixa-embalado". DIRECTORY_SEPARATOR ."form.phtml");
    }

    public function editAction()
    {
        parent::editAction();
        $this->view->render("caixa-embalado". DIRECTORY_SEPARATOR ."form.phtml");
    }
}