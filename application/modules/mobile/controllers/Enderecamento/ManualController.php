<?php
use Wms\Controller\Action;
use Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Mobile_Enderecamento_ManualController extends Action
{
    public function indexAction()
    {
        $recebimentoService = new \Mobile\Service\Recebimento($this->em);
        $this->view->recebimentos = $recebimentoService->listarRecebimentosNaoEnderecados(null);
    }

    public function lerCodigoBarrasAction()
    {
        $params = $this->_getAllParams();

        if (isset($params['produto']) && )

    }

    public function validarEnderecoAction() {

    }

    public function confirmarOperacaoAction(){

    }
}

