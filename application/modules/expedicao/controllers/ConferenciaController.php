<?php
use Wms\Module\Web\Controller\Action;

class Expedicao_ConferenciaController extends Action
{

    public function indexAction()
    {
        $idExpedicao = $this->getRequest()->getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->em->getRepository('wms:Expedicao');
        $cargas = $ExpedicaoRepo->getCargas($idExpedicao);
        $centrais = $ExpedicaoRepo->getCentralEntregaPedidos($idExpedicao);
        $this->view->centraisEntrega = $centrais;
        $this->view->cargas = $cargas;
    }

    public function finalizarAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $idExpedicao      = $request->getParam('id');
            $senhaDigitada    = $request->getParam('senhaConfirmacao');
            $centrais         = $request->getParam('centrais');
            $origin           = $request->getParam('origin');
            $senhaAutorizacao = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 23, 'constante' => 'SENHA_FINALIZAR_EXPEDICAO'));
            $senhaAutorizacao = $senhaAutorizacao->getValor();
            $submit           = $request->getParam('btnFinalizar');

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo    = $this->em->getRepository('wms:Expedicao');

            if ($submit == 'semConferencia') {
                if ($senhaDigitada == $senhaAutorizacao) {
                    //$expedicaoRepo->finalizacarga($idExpedicao);
                    $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],false, 'S');
                } else {
                    $result = 'Senha informada não é válida';
                }
            } else {
                $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],true, 'M');
            }

            if (is_string($result)) {
                $this->addFlashMessage('error', $result);
            } else {
                $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
            }

            if ($origin == "expedicao") {
                $this->_redirect('/expedicao');
            } else {
                $this->_redirect('/expedicao/os/index/id/' . $idExpedicao);
            }
        }
    }
}