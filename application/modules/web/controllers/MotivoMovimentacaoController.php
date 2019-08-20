<?php

use Wms\Module\Web\Controller\Action\Crud;
use Wms\Module\Web\Page;

class Web_MotivoMovimentacaoController extends Crud
{

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        $this->entityName = "Enderecamento\MotivoMovimentacao";
        parent::__construct($request, $response, $invokeArgs);
    }

    public function indexAction()
    {
        $grid = new \Wms\Module\Web\Grid\Enderecamento\MotivoMovimentacao();
        $this->view->grid = $grid->init()->render();
    }

    public function addAction()
    {
        try {
            parent::addAction();
            $this->renderScript("motivo-movimentacao". DIRECTORY_SEPARATOR ."form.phtml");
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function editAction()
    {
        try {
            parent::editAction();
            Page::configure(array(
                'buttons' => array(
                    array(
                        'label' => 'Voltar',
                        'cssClass' => 'btnBack',
                        'urlParams' => array(
                            'action' => 'index',
                            'id' => null
                        ),
                        'tag' => 'a'
                    ),
                    array(
                        'label' => 'Adicionar novo',
                        'cssClass' => 'btnAdd',
                        'urlParams' => array(
                            'action' => 'add',
                            'id' => null
                        ),
                        'tag' => 'a'
                    ),
                    array(
                        'label' => 'Salvar',
                        'cssClass' => 'btnSave'
                    )
                )
            ));
            $this->renderScript("motivo-movimentacao". DIRECTORY_SEPARATOR ."form.phtml");
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
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