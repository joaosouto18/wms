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
        $params = $this->_getAllParams();

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

            if (isset($params['codCargaExterno']) && !empty($params['codCargaExterno'])) {
                $cargaRepo = $this->em->getRepository('wms:Expedicao\Carga');
                $entityCarga = $cargaRepo->findOneBy(array('codCargaExterno' => $params['codCargaExterno']));
                $idExpedicao = $entityCarga->getExpedicao()->getId();
            }

            if ($submit == 'semConferencia') {
                if ($senhaDigitada == $senhaAutorizacao) {
                    $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],false, 'S');
                    if ($result == true) {
                        $result = 'Expedição Finalizada com Sucesso!';
                    }
                    $this->addFlashMessage('success', $result);
                } else {
                    $result = 'Senha informada não é válida';
                    $this->addFlashMessage('error', $result);
                }
                if ($origin == "expedicao") {
                    $this->_redirect('/expedicao');
                } else {
                    $this->_redirect('/expedicao/os/index/id/' . $idExpedicao);
                }
            } else {
                $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais,true, 'M');
                if ($result == 0) {
                    $result = 'Expedição Finalizada com Sucesso!';
                }
            }
            $this->_helper->json(array('result' => $result));

        }
    }
}