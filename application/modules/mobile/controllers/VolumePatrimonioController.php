<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\SenhaLiberacao;


class Mobile_VolumePatrimonioController  extends Action
{
    /**
     * De acordo com o parâmetro TipoVolumePatrimonio lista cargas ou clientes
     */
    public function carregaTipoAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $idExpedicao = $this->_getParam('idExpedicao');
        $parametroTipoVolumePatrimonio = 'carga';

        if ($parametroTipoVolumePatrimonio == 'carga') {
            $tipoVolume = $expedicaoRepo->getCargaExternoEmbalados($idExpedicao,null);
        } else {
            $tipoVolume = array();
        }

        $this->view->idExpedicao = $idExpedicao;
        $this->view->tipoVolume = $tipoVolume;
    }

    public function lerCodigoBarraVolumeAction()
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('leitura');
        $idExpedicao = $this->_getParam('idExpedicao');

        //conferencia do volume no box
        $conferenciaBox = $this->_getParam('box', null);
        //existe placa na conferencia de transbordo
        $placa = $this->_getParam('placa', null);
        //conferencia do volume no recebimento de transbordo
        $recebimentoTransbordo = $this->_getParam('recebimento-transbordo', null);

        if ($conferenciaBox && ($placa == null)) {
            $this->view->urlFormAction = 'confere-volume-expedicao';
        }
        elseif($placa) {
            $this->view->urlFormAction = 'confere-volume-exp-transbordo';
        }
        elseif ($recebimentoTransbordo) {
            $this->view->urlFormAction = 'confere-volume-rec-transbordo';
        }
        else {
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepository */
            $ExpedicaoRepository = $this->em->getRepository('wms:Expedicao');
            $this->view->volumesPatrimonio  = $ExpedicaoRepository->getVolumesPatrimonio($idExpedicao);
            $this->view->urlFormAction      = 'vincula-volume';
        }

        $this->view->idExpedicao    = $idExpedicao;
        $this->view->idTipoVolume   = $this->_getParam('idTipoVolume');
    }

    public function vinculaVolumeAction()
    {
        $volume         = $this->_getParam('volume');
        $idExpedicao    = $this->_getParam('idExpedicao');
        $idTipoVolume   = $this->_getParam('idTipoVolume');
        if ($this->_request->isXmlHttpRequest()) {
            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
            $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
            try {
                $expVolumePatrimonioRepo->vinculaExpedicaoVolume($volume, $idExpedicao, $idTipoVolume);
                $this->createXml('success', 'Volume encontrado', "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/tipo-conferencia/volume/volume/$volume/idTipoVolume/$idTipoVolume");
            } catch(Exception $e) {
                $this->createXml('error', $e->getMessage());
            }

        } else {
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'conferencia' => 'volume','idTipoVolume'=> $idTipoVolume));
        }
    }

    public function confereVolumeExpedicaoAction()
    {
        $volume         = $this->_getParam('volume');
        $idExpedicao    = $this->_getParam('idExpedicao');

        if ($this->_request->isXmlHttpRequest()) {
            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
            $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
            try {
                $retorno = $expVolumePatrimonioRepo->confereExpedicaoVolume($volume, $idExpedicao);
                if ($retorno['redirect'] == true) {
                    $this->bloqueioOs($idExpedicao, $retorno['msg'], false);
                    $this->createXml('error', $retorno['msg'],"/mobile/volume-patrimonio/liberar-os/idExpedicao/$idExpedicao/tipo-conferencia/volume");
                }
                $this->createXml('success', 'Volume '. $volume . ' conferido');

            } catch(Exception $e) {
                $this->createXml('error', $e->getMessage());
            }

        } else {
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'conferencia' => 'volume'));
        }
    }

    public function confereVolumeRecTransbordoAction()
    {
        $volume         = $this->_getParam('volume');
        $idExpedicao    = $this->_getParam('idExpedicao');
        if ($this->_request->isXmlHttpRequest()) {
            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
            $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
            try {
                $expVolumePatrimonioRepo->confereVolumeRecTransbordo($idExpedicao, $volume);
                $this->createXml('success', 'Recebimento de Volume em transbordo conferido');
            } catch(Exception $e) {
                $this->createXml('error', $e->getMessage());
            }

        } else {
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'conferencia' => 'volume'));
        }
    }

    public function confereVolumeExpTransbordoAction()
    {
        $volume         = $this->_getParam('volume');
        $idExpedicao    = $this->_getParam('idExpedicao');
        if ($this->_request->isXmlHttpRequest()) {
            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
            $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
            try {
                $expVolumePatrimonioRepo->confereVolumeExpTransbordo($idExpedicao, $volume);
                $this->createXml('success', 'Conferência de volume de transbordo concluida');
            } catch(Exception $e) {
                $this->createXml('error', $e->getMessage());
            }

        } else {
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'conferencia' => 'volume'));
        }
    }

    public function fecharCaixaAction()
    {
        $volume         = $this->_getParam('volume');
        $idExpedicao    = $this->_getParam('idExpedicao');
        /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
        try {
            $expVolumePatrimonioRepo->fecharCaixa($idExpedicao, $volume);
            $this->_helper->messenger('success', 'Volume '. $volume. ' fechado com sucesso');
        } catch (Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'conferencia' => 'volume'));
    }

    protected function bloqueioOs($idExpedicao, $motivo, $render = true)
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio($motivo);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

        $andamentoRepo->save($motivo, $idExpedicao);
        $this->_helper->messenger('error', $motivo);

        if ($render == true) {
            $form = new SenhaLiberacao();
            $form->setDefault('idExpedicao', $idExpedicao);
            $this->view->form = $form;
            $this->render('bloqueio');
        }
    }

    protected function desbloqueioOs($idExpedicao, $motivo)
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio(NULL);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

        $andamentoRepo->save($motivo, $idExpedicao);
        $this->_helper->messenger('success', $motivo);
    }

    public function liberarOsAction()
    {
        $request     = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $placa = $this->getRequest()->getParam('placa', null);
        $volume = $this->getRequest()->getParam('volume', null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senha');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $this->desbloqueioOs($idExpedicao, 'Ordem de serviço liberada');
                $this->redirect('ler-codigo-barra-volume', 'volume-patrimonio','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa, 'tipo-conferencia' => $tipoConferencia, 'volume' => $volume));
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }
        }

        $form = new SenhaLiberacao();
        $form->setDefault('idExpedicao', $idExpedicao);
        $form->newUrl('volume-patrimonio', 'liberar-os');
        $this->view->form = $form;
        $this->render('bloqueio');

    }

}