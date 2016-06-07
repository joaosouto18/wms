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
        $this->view->idExpedicao = $idExpedicao;
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
            $senhaAutorizacao = $this->getSystemParameterValue('SENHA_FINALIZAR_EXPEDICAO');
            $submit           = $request->getParam('btnFinalizar');

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo    = $this->em->getRepository('wms:Expedicao');

            if (isset($params['codCargaExterno']) && !empty($params['codCargaExterno'])) {
                $cargaRepo = $this->em->getRepository('wms:Expedicao\Carga');
                $entityCarga = $cargaRepo->findOneBy(array('codCargaExterno' => $params['codCargaExterno'], 'expedicao'=>$idExpedicao));
                $idExpedicao = $entityCarga->getExpedicao()->getId();
            }
            $redirect = false;
            if ($submit == 'semConferencia') {
                if ($senhaDigitada == $senhaAutorizacao) {
                    $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],false, 'S');
                    if ($result == 'true') {
                        $result = 'Expedição Finalizada com Sucesso!';
                        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                            $this->addFlashMessage('success', $result);
                            $this->_redirect('/produtividade/carregamento/index/id/' . $idExpedicao);
                        }
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

                exit;
                if ($origin == 'coletor') {
                    if ($result == 'true') {
                        $result = 'Expedição Finalizada com Sucesso!';
                        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                            $redirect = true;
                        }
                    }
                    $this->addFlashMessage('success', $result);
                    $this->_redirect('/mobile/expedicao/index/idCentral/'.$centrais);
                }
                if ($result == 'true') {
                    if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                        $redirect = true;
                    }
                    $result = 'Expedição Finalizada com Sucesso!';
                }
            }
            $this->_helper->json(array('result' => $result,
                                       'redirect' => $redirect,
                                       'idExpedicao'=>$idExpedicao));
        }
    }
}