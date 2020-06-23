<?php

use \Wms\Domain\Entity\Integracao\AcaoIntegracao,
    \Wms\Module\Integracao\Form\AcaoIntegracaoForm,
    Wms\Module\Web\Page,
    \Wms\Module\Integracao\Grid\IntegracaoGrid;

class Integracao_GerenciamentoController extends \Wms\Controller\Action
{
    public function configurePage($buttons = [])
    {
        Page::configure(array('buttons' => $buttons));
    }

    public function indexAction()
    {
        $grid = new \Wms\Module\Integracao\Grid\IntegracaoGrid();
        $this->view->grid = $grid->init();
        $buttons[] = array(
            'label' => 'Nova Integração',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'integracao',
                'controller' => 'gerenciamento',
                'action' => 'save'
            ),
            'tag' => 'a'
        );

        $this->configurePage($buttons);
    }

    public function editAction()
    {
        $this->view->form = new AcaoIntegracaoForm();
        $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'form-cadastral.phtml');
    }

    public function saveAction()
    {

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