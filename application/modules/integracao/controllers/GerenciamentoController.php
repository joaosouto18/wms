<?php


class Integracao_GerenciamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $grid = new \Wms\Module\Integracao\Grid\IntegracaoGrid();

        $this->view->grid = $grid->init();
    }

    public function viewDetailIntegracaoAjaxAction()
    {
        $this->view->integracao = $this->em->find(\Wms\Domain\Entity\Integracao\AcaoIntegracao::class, $this->getRequest()->getParam('id'));
        $this->renderScript('gerenciamento' . DIRECTORY_SEPARATOR . 'view-detail-integracao.phtml');
    }
}