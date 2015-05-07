<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria;

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
//        var_dump('teste'); exit;
        $form = new FiltroExpedicaoMercadoria();
        $params = $this->_getAllParams();
//        exit;

        $request = $this->getRequest();
        $idExpedicao      = $request->getParam('id');


        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senhaConfirmacao');
            $centrais         = $request->getParam('centrais');
            $origin           = $request->getParam('origin');
            $senhaAutorizacao = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'SENHA_AUTORIZAR_DIVERGENCIA'));
            $senhaAutorizacao = $senhaAutorizacao->getValor();
            $submit           = $request->getParam('btnFinalizar');
/*
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo    = $this->em->getRepository('wms:Expedicao');

        /*

if ($submit == 'semConferencia') {
    if ($senhaDigitada == $senhaAutorizacao) {
        $expedicaoRepo->finalizacarga($idExpedicao);
        $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],false);
    } else {
        $result = 'Senha informada não é válida';
    }
} else {
    $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],true);
}
*/
if (is_string($result = false)) {
    $this->addFlashMessage('error', $result);
} else {
    $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
}

if ($origin != "expedicao") {
    $this->_redirect('/expedicao/os/index/id/' . $idExpedicao);
}
}
        $params['idExpedicao'] = $idExpedicao;

        $this->_helper->json(array('msg' => $result));

        echo '<script type="text/javascript">alert("digfhgdhig")</script>'; die();

    }
}