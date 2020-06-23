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
        $grid = new IntegracaoGrid();
        $this->view->grid = $grid->init();
        $buttons[] = array(
            'label' => 'Nova Integração',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'integracao',
                'controller' => 'gerenciamento',
                'action' => 'acao-integracao-form'
            ),
            'tag' => 'a'
        );

        $this->configurePage($buttons);
    }

    public function acaoIntegracaoFormAction()
    {
        $idIntegracao = $this->getRequest()->getParam('id');
        $this->view->form = new AcaoIntegracaoForm();
        try {

            if (!empty($idIntegracao)) {
                /** @var AcaoIntegracao $acaoIntegracao */
                $acaoIntegracao = $this->em->find(AcaoIntegracao::class, $idIntegracao);
                if (empty($acaoIntegracao)) throw new Exception("A ação integração de código $idIntegracao não foi encontrada!");

                $this->view->form->setDefaults($acaoIntegracao->toArray());
            }
            $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'acao-integracao-form.phtml');
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('index');
        }
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