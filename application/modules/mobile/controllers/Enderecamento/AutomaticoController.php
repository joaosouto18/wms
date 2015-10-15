<?php
use Wms\Controller\Action;

class Mobile_Enderecamento_AutomaticoController extends Action
{
    public function listaRecebimentoAction()
    {
        $recebimentoService = new \Mobile\Service\Recebimento($this->em);
        $this->view->recebimentos = $recebimentoService->listarRecebimentosNaoEnderecados();
    }

}

