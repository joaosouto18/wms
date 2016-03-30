<?php
use Wms\Controller\Action;
use Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Mobile_Enderecamento_ManualController extends Action
{
    public function indexAction()
    {
        $recebimentoService = new \Mobile\Service\Recebimento($this->em);
        $this->view->recebimentos = $recebimentoService->listarRecebimentosNaoEnderecados($status = RecebimentoEntity::STATUS_FINALIZADO, 5);
    }

    public function lerCodigoBarrasAction()
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('leitura');
    }

}

