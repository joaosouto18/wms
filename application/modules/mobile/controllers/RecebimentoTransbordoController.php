<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Module\Mobile\Form\SenhaLiberacao,
    Wms\Domain\Entity\Expedicao;

class Mobile_RecebimentoTransbordoController extends Action
{

    public function lerCodigoBarrasAction()
    {
        try {

            $sessaoColetor = new \Zend_Session_Namespace('coletor');
            $sessaoColetor->parcialmenteFinalizado = true;

            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->setLayout();

            if ( ($Expedicao->validacaoExpedicao() == false) || ($Expedicao->osLiberada() == false)) {
                $this->mensagemColetor($Expedicao);
            }

            if ($Expedicao->possuiEmbalado() == true) {
                $this->_forward('tipo-conferencia','recebimento-transbordo','mobile', array('placa' => $Expedicao->getPlaca()));
            }

            $this->view->placa = $Expedicao->getPlaca();
            $this->view->idExpedicao = $Expedicao->getIdExpedicao();

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('error', $e->getMessage(), "/mobile/ordem-servico/conferencia-expedicao");
            } else {
                $this->redirect('conferencia-expedicao', 'ordem-servico');
            }
        }

    }

    public function tipoConferenciaAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao',null);
        $menu = array(
            1 => array(
                'url' => '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao.'/recebimento-transbordo/1',
                'label' => 'CONF. VOLUME',
            ),
            2 => array(
                'url' => '/mobile/recebimento-transbordo/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/tipo-conferencia/naoembalado',
                'label' => 'CONF. NÃO EMBALADO',
            )
        );

        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    /**
     * @param $Expedicao
     */
    public function mensagemColetor($Expedicao)
    {
        $this->_helper->messenger($Expedicao->getStatus(), $Expedicao->getMessage());
        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml($Expedicao->getRetorno(), $Expedicao->getMessage(), $Expedicao->getRedirect());
        } else {
            $this->_redirect($Expedicao->getRedirect());
        }
    }

    /** @var \Wms\Domain\Entity\Expedicao\VEtiquetaSeparacao $etiqueta */
    private function validaEtiqueta($idExpedicao, $etiqueta, $codigoBarras)
    {
        if ($etiqueta == NULL) {

            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('error', 'Etiqueta '.$codigoBarras.' não encontrada');
            } else {
                $this->_helper->messenger('info', 'Etiqueta '.$codigoBarras.' não encontrada');
                $this->redirect('ler-codigo-barras', 'recebimento-transbordo','mobile', array('idExpedicao' => $idExpedicao));
            }
            return false;
        }

        if ($etiqueta->getCodExpedicao() != $idExpedicao) {
            $this->bloqueioOs($idExpedicao, 'Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta->getCodExpedicao(), false);
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('error', 'Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta->getCodExpedicao(), "/mobile/recebimento-transbordo/liberar-os/idExpedicao/$idExpedicao");
            } else {
                $this->redirect('liberar-os', 'recebimento-transbordo','mobile', array('idExpedicao' => $idExpedicao));
            }
            return false;
        }

        if ($etiqueta->getCodStatus() != EtiquetaSeparacao::STATUS_CONFERIDO) {
            $siglaRepo  = $this->em->getRepository('wms:Util\Sigla');
            $sigla = $siglaRepo->findOneBy(array('id'=>$etiqueta->getCodStatus()));
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml("error", 'ETIQUETA '. $sigla->getSigla());
            }
            return false;
        }

        return true;
    }

    public function recebeTransbordoAction()
    {
        $idExpedicao                 = $this->getRequest()->getParam('idExpedicao');
        $codigoBarras                = $this->getRequest()->getParam('etiquetaSeparacao');

        $LeituraColetor = new LeituraColetor();
        $codigoBarras   = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);

        /** @var \Wms\Domain\Entity\Expedicao\VEtiquetaSeparacaoRepository $vEtiquetaRepo */
        $vEtiquetaRepo  = $this->em->getRepository('wms:Expedicao\VEtiquetaSeparacao');
        $etiquetaEn = $vEtiquetaRepo->findOneBy(array('codBarras' => $codigoBarras));

        if ($this->validaEtiqueta($idExpedicao, $etiquetaEn, $codigoBarras) != false) {

            $this->confereEtiqueta($codigoBarras);

            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('success', 'Etiqueta conferida com sucesso');
            } else {
                $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
                $this->redirect('ler-codigo-barras', 'recebimento-transbord','mobile', array('idExpedicao' => $idExpedicao));
            }
        }

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

    /**
     * @param $idEtiqueta
     */
    protected function confereEtiqueta($idEtiqueta)
    {
        $sessao = new \Zend_Session_Namespace('coletor');

        $date = new \DateTime();
        $date = $date->format('Y-m-d H:i:s');
        $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOSTransbordo = :osID , es.dataConferenciaTransbordo = :dataConferencia where es.id = :idEtiqueta');

        $q->setParameter('status', EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO);
        $q->setParameter('dataConferencia', $date);
        $q->setParameter('osID', $sessao->osID);
        $q->setParameter('idEtiqueta', $idEtiqueta);
        $q->execute();
    }

    public function liberarOsAction()
    {
        $request     = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senha');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $this->desbloqueioOs($idExpedicao, 'Ordem de serviço liberada');
                $this->redirect('ler-codigo-barras', 'recebimento-transbordo','mobile', array('idExpedicao' => $idExpedicao, 'tipo-conferencia' => $tipoConferencia));
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }
        }

        $form = new \Wms\Module\Mobile\Form\SenhaLiberacaoRecTransbordo();
        $form->setDefault('idExpedicao', $idExpedicao);
        $this->view->form = $form;
        $this->render('bloqueio');

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

    public function finalizadoAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao');

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_CONFERIDO);
        if (count($result) > 0) {
            $this->createXml('error', 'Faltam '.count($result).' produtos a serem recebidos');
        } else {
            $this->createXml('success','Todos os produtos já foram recebidos');
        }
    }

    public function produtividadeAction()
    {

    }

    public function equipeProdutividadeRecebimentoAction()
    {
        $operadores     = $this->_getParam('mass-id');
        $idExpedicao    = $this->_getParam('idExpedicao');

        $expedicaoRepo        = $this->em->getRepository('wms:Expedicao');
        $entityExpedicao      = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));

        if ($operadores && $idExpedicao) {

            if (!$entityExpedicao) {
                $this->addFlashMessage('error', 'Expedição não encontrada!');
                $this->_redirect('/mobile/recebimento-transbordo/equipe-produtividade-recebimento');
            }

            /** @var \Wms\Domain\Entity\Recebimento\EquipeRecebimentoTransbordoRepository $equipeRecebTransRepo */
            $equipeRecebTransRepo = $this->em->getRepository('wms:Recebimento\EquipeRecebimentoTransbordo');
            try {
                $equipeRecebTransRepo->vinculaOperadores($idExpedicao,$operadores);
                $this->_helper->messenger('success', 'Operadores vinculados ao recebimento com sucesso');
                $this->_redirect('mobile');
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->_em->getRepository('wms:Usuario');
        $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_RECEBIMENTO"));
        $this->view->idExpedicao    = $idExpedicao;

    }

    public function equipeProdutividadeExpedicaoAction()
    {
        $operadores     = $this->_getParam('mass-id');
        $placa          = $this->_getParam('placa');
        $idExpedicao    = $this->_getParam('idExpedicao');

        $cargaRepo        = $this->em->getRepository('wms:Expedicao\Carga');
        $entityCarga      = $cargaRepo->findOneBy(array('placaCarga' => $placa));

        if ($operadores) {

            if (isset($entityCarga) && empty($idExpedicao)) {
                $idExpedicao = $entityCarga->getExpedicao()->getId();
            }

            $expedicaoRepo        = $this->em->getRepository('wms:Expedicao');
            $entityExpedicao      = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));

            if (!$entityExpedicao) {
                $this->addFlashMessage('error', 'Expedição não encontrada!');
                $this->_redirect('/mobile/recebimento-transbordo/equipe-produtividade-expedicao');
            }

            /** @var \Wms\Domain\Entity\Expedicao\EquipeExpedicaoTransbordoRepository $equipeExpTransbordoRepository */
            $equipeExpTransbordoRepository = $this->em->getRepository('wms:Expedicao\EquipeExpedicaoTransbordo');
            try {
                $equipeExpTransbordoRepository->vinculaOperadores($idExpedicao,$operadores);
                $this->_helper->messenger('success', 'Operadores vinculados a expedição com sucesso');
                $this->_redirect('mobile');
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->_em->getRepository('wms:Usuario');
        $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_EXPEDICAO_TRANSBORDO"));
    }

}

