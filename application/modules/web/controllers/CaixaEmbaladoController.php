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
        $this->view->grid = $grid->init()->render();;
    }

    public function addAction()
    {
        try {

            //TODO Regra momentânia, até liberar o cadastro para N caixas;
            $entity = $this->em->getRepository("wms:Expedicao\CaixaEmbalado")->findOneBy(['isAtiva' => true, 'isDefault' => true]);
            if (!empty($entity)) throw new Exception("Por momento o sistema permite apenas <strong>UMA</strong> caixa cadastrada");

            parent::addAction();
            $this->renderScript("caixa-embalado". DIRECTORY_SEPARATOR ."form.phtml");
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function editAction()
    {
        try {
            parent::editAction();
            $this->renderScript("caixa-embalado". DIRECTORY_SEPARATOR ."form.phtml");
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function setDefaultAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the delete action');

            $this->repository->changeDefault($id);
            $this->_helper->messenger('success', 'Registro deletado com sucesso');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->redirect('index');
    }

    public function deleteAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the delete action');

            $this->repository->exclusaoLogica($id);

            $this->_helper->messenger('success', 'Registro deletado com sucesso');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->redirect('index');
    }
}