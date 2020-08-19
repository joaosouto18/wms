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
                $filtros = $this->em->getRepository(\Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::class)->findBy(['acaoIntegracao' => $acaoIntegracao]);

                $this->view->form->setDefaults($acaoIntegracao->toArray(), $filtros);
            }
            $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'acao-integracao-form.phtml');
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function salvarAction()
    {
        $params = $this->getRequest()->getParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        try {
            $this->em->getRepository(AcaoIntegracao::class)->save($params);
            $this->addFlashMessage('success', 'Integração salva com sucesso');
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    public function viewDetailIntegracaoAjaxAction()
    {
        $this->view->integracao = $this->em->find(AcaoIntegracao::class, $this->getRequest()->getParam('id'));
        $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'view-detail-integracao.phtml');
    }

    public function toggleLogIntegracaoAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');
        try {
            $this->em->getRepository(AcaoIntegracao::class)->toggleLog($id, $status);
            $this->addFlashMessage('success', 'Registro de log alterado com sucesso');
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    public function refreshExecIntegracaoAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            $this->em->getRepository(AcaoIntegracao::class)->refreshExec($id);
            $this->addFlashMessage('success', 'Execução reiniciada com sucesso');
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }
        $this->redirect('index');
    }
}