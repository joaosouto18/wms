<?php

use \Wms\Domain\Entity\Integracao\AcaoIntegracao;

class Integracao_GerenciamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $grid = new \Wms\Module\Integracao\Grid\IntegracaoGrid();

        $this->view->grid = $grid->init();
    }

    public function editAction()
    {
        $this->view->form = new \Wms\Module\Integracao\Form\CadastrarIntegracaoForm();
        //$this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'form-cadastral.phtml');
    }

    public function viewDetailIntegracaoAjaxAction()
    {
        $this->view->integracao = $this->em->find(AcaoIntegracao::class, $this->getRequest()->getParam('id'));
        $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'view-detail-integracao.phtml');
    }

    public function turnOffLogIntegracaoAjaxAction()
    {
        $this->view->integracao = $this->em->find(AcaoIntegracao::class, $this->getRequest()->getParam('id'));
        $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'view-detail-integracao.phtml');
    }
}