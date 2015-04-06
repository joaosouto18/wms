<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Module\Mobile\Form\SenhaLiberacao,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Domain\Entity\Expedicao;

class Mobile_ExpedicaoController extends Action
{

    protected $bloquearOs = 'S';

    public function indexAction()
    {
        $menu = array(
            1 => array(
                'url' => 'ordem-servico/centrais-entrega',
                'label' => 'CONF. EXPEDIÇÃO',
            ),
            2 => array(
                'url' => 'ordem-servico/centrais-entrega/transbordo/1',
                'label' => 'CONF. TRANSBORDO',
            ),
            3 => array (
                'url' => 'ordem-servico/recebimento-transbordo',
                'label' => 'RECB. TRANSBORDO',
            ),
            4 => array (
                'url' => 'onda-ressuprimento/listar-ondas',
                'label' => 'ONDA DE RESSUPRIMENTO',
            )
        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function tipoConferenciaAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao',null);
        $placa = $this->_getParam('placa',null);
        if ($placa != null) {
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao.'/placa/'.$placa;
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/tipo-conferencia/naoembalado/placa/'.$placa;
            $urlEmbalado = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao;
        } else {
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/tipo-conferencia/naoembalado';
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao;
            $urlEmbalado = '/mobile/volume-patrimonio/carrega-tipo/idExpedicao/'.$idExpedicao;
        }
        $menu = array(
            1 => array(
                'url' => $url.'/box/1',
                'label' => 'CONF. VOLUME',
            ),
            2 => array(
                'url' => $urlNEmbalado,
                'label' => 'CONF. NÃO EMBALADO',
            ),
            3 => array (
                'url' => $urlEmbalado,
                'label' => 'CONF. EMBALADO',
            ),
        );

        if ($placa != null) {
            unset($menu[3]);
        }

        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function finalizarAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $sessao = new \Zend_Session_Namespace('coletor');
        $request = $this->getRequest();
        $idExpedicao      = $request->getParam('idExpedicao');
        $central          = $sessao->centralSelecionada;

        $result = $ExpedicaoRepo->finalizarExpedicao($idExpedicao, $central, true);
        if (is_string($result)) {
            $this->addFlashMessage('error', $result);
        } else {
            $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
        }
        $this->_redirect('mobile/ordem-servico/conferencia-expedicao/idCentral/'.$central);

    }

    public function selecionaPlacaAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('idExpedicao');
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $sessaoColetor->parcialmenteFinalizado = true;

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $placas = $expedicaoRepo->getPlacasByExpedicaoCentral($idExpedicao);

        $this->view->placas = $placas;
    }

    protected function validacaoEtiqueta($codigoBarras)
    {
        $idExpedicao        = $this->getRequest()->getParam('idExpedicao');
        $placa              = $this->getRequest()->getParam('placa', null);
        $tipoConferencia    = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);
        $volume             = $this->getRequest()->getParam('volume', null);
        $sessao             = new \Zend_Session_Namespace('coletor');
        $idCentral          = $sessao->centralSelecionada;

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo  = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $etiqueta = $etiquetaRepo->getEtiquetaByExpedicaoAndId($codigoBarras);
        if (count($etiqueta) == 0) {

            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('error', 'Etiqueta '.$codigoBarras.' não encontrada');
            } else {
                $this->_helper->messenger('info', 'Etiqueta '.$codigoBarras.' não encontrada');
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
            }

            return false;
        } else {
            if ($etiqueta[0]['codExpedicao'] != $idExpedicao) {
                $this->bloqueioOs($idExpedicao, 'Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'], false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'], $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
        }

        //Se o tipo de conferencia for nao embalado, nao se pode bipar produtos que devem ser embalados
        if ($tipoConferencia == 'naoembalado' && $etiqueta[0]['embalado'] == 'S') {
            $this->createXml('error', 'Produtos embalados devem ser vinculados a um patrimônio');
        }

        //Verifico se a etiqueta pertence a carga selecionada
        if  (!is_null($idTipoVolume) && !empty($idTipoVolume)) {
            if ($idTipoVolume != $etiqueta[0]['codCargaExterno']) {
                $this->bloqueioOs($idExpedicao, 'Etiqueta '.$codigoBarras.' não pertence a carga selecionada - Carga Correta:' . $etiqueta[0]['codCargaExterno'], false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'], $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
        }

        //Etiqueta pertence a central Selecionada e a placa selecionada
        if  (!is_null($placa) && !empty($placa)) {
            if ($etiqueta[0]['pontoTransbordo'] != $idCentral) {
                $this->bloqueioOs($idExpedicao, 'Etiqueta não pertence a central ' . $idCentral, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a central ' . $idCentral, $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
            if ($etiqueta[0]['placaCarga'] != $placa) {
                $this->bloqueioOs($idExpedicao, 'Etiqueta não pertence a placa ' . $placa, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a placa ' . $placa, $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
        } else {
            if ($etiqueta[0]['codEstoque'] != $idCentral) {
                $this->bloqueioOs($idExpedicao, 'Etiqueta não pertence a central ' . $idCentral, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a central ' . $idCentral, $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
        }

        return $etiqueta;
    }

    public function validaStatusEtiqueta($idExpedicao, $status, $sessaoColetor)
    {
        $obrigaRealizarRecebimento = $sessaoColetor->ObrigaBiparEtiquetaProduto;
        $placa = $this->getRequest()->getParam('placa',null);

        switch ($status)
        {
            case EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO:
            case EtiquetaSeparacao::STATUS_PENDENTE_CORTE:
            case EtiquetaSeparacao::STATUS_CORTADO:
            case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                return false;
                break;
            case EtiquetaSeparacao::STATUS_CONFERIDO:
                if ($sessaoColetor->parcialmenteFinalizado == false) {
                    return false;
                } else {
                    if ($obrigaRealizarRecebimento == 'S') {
                        $this->bloqueioOs($idExpedicao, 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', false);
                        if ($this->_request->isXmlHttpRequest()) {
                            $this->createXml('error', 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', $this->createUrlMobile());
                        } else {
                            $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        }
                        return false;
                    }
                }
                break;
        }
        return true;
    }

    public function extraiCodigoBarras($etiquetas)
    {
        $codBarras = "";
        foreach ($etiquetas as $etiqueta) {
            $codBarras = $codBarras . '-' . $etiqueta['codBarrasProduto'];
        }
        return $codBarras;
    }

    public function geraArrayCodigoBarras ($value)
    {
        $result = explode('-', $value);
        unset($result[0]);
        return $result;
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
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senha');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $this->desbloqueioOs($idExpedicao, 'Ordem de serviço liberada');
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa, 'tipo-conferencia' => $tipoConferencia, 'volume' => $volume, 'idTipoVolume' => $idTipoVolume));
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }
        }

        $form = new SenhaLiberacao();
        $form->setDefault('idExpedicao', $idExpedicao);
        $this->view->form = $form;
        $this->render('bloqueio');

    }

    public function confirmaConferenciaAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('idExpedicao');
        $idEtiqueta     = $this->getRequest()->getParam('idEtiqueta');
        $produto        = $this->getRequest()->getParam('produto');
        $placa = $this->getRequest()->getParam('placa',null);

        $this->confereEtiqueta($idEtiqueta);

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
        $andamentoRepo->save('Botão confirmar conferência '.$produto, $idExpedicao);

        $this->addFlashMessage('success', 'Produto conferido com sucesso');
        $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
    }

    /**
     * @param $idEtiqueta
     */
    protected function confereEtiqueta($idEtiqueta, $volume = null)
    {
        $sessao = new \Zend_Session_Namespace('coletor');

        $date = new \DateTime();
        $date = $date->format('Y-m-d H:i:s');

        if (isset($sessao->parcialmenteFinalizado) && $sessao->parcialmenteFinalizado == true) {
            $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOSTransbordo = :osID , es.dataConferenciaTransbordo = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
            $q->setParameter('status', EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO);
        } else {
            $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOS = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
            $q->setParameter('status', EtiquetaSeparacao::STATUS_CONFERIDO);
        }

        $q->setParameter('dataConferencia', $date);
        $q->setParameter('osID', $sessao->osID);
        $q->setParameter('idEtiqueta', $idEtiqueta);
        $q->setParameter('volumePatrimonio', $volume);
        $q->execute();
    }

    public function buscarEtiquetasAction()
    {
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $idExpedicao = $this->getRequest()->getParam('idExpedicao');
        $etiquetaSeparacao = $this->getRequest()->getParam('etiquetaSeparacao');
        $LeituraColetor = new LeituraColetor();
        $etiquetaSeparacao = $LeituraColetor->retiraDigitoIdentificador($etiquetaSeparacao);
        $placa = $this->getRequest()->getParam('placa',null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $volume = $this->getRequest()->getParam('volume', null);

        $etiqueta = $this->validacaoEtiqueta($etiquetaSeparacao);

        if ($etiqueta == false) {
            return false;
        }

        $return = $this->validaStatusEtiqueta ($idExpedicao, $etiqueta[0]['codStatus'], $sessaoColetor);

        if ($return == false) {
            if ($etiqueta[0]['status'] == 'EXPEDIDO TRANSBORDO') {
                $this->_helper->messenger('info', 'Etiqueta de transbordo já conferida');
                $mensagem = 'Etiqueta de transbordo já conferida';
            } else {
                $this->_helper->messenger('info', 'ETIQUETA '. $etiqueta[0]['status']);
                $mensagem = 'ETIQUETA '. $etiqueta[0]['status'];
            }
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml("error", $mensagem);
            } else {
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
            }
            return false;
        }

        if ($sessaoColetor->parcialmenteFinalizado == true) {
            $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
            if ($obrigaBiparEtiqueta == 'N') {
                $this->confereEtiqueta($etiquetaSeparacao, $volume);
                $this->addFlashMessage('success', 'Produto conferido com sucesso');
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Produto conferido com sucesso');
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
            }
        }

        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto   = $LeituraColetor->analisarCodigoBarras($etiquetaProduto);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $this->bloqueioOs($idExpedicao, 'Produto '. $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] .' não confere com a etiqueta de separação ' . $etiquetaProduto, false);

                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error",'Etiqueta produto não confere com etiqueta de separação',$this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            }
        }

        $this->confereEtiqueta($etiquetaSeparacao, $volume);

        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml('success', 'Etiqueta conferida com sucesso');
        } else {

            $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
        }

    }

    public function lerCodigoBarrasAction()
    {
        try {

            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->setLayout();

            if ( ($Expedicao->validacaoExpedicao() == false) || ($Expedicao->osLiberada() == false)) {
                $this->mensagemColetor($Expedicao);
            }

            if ($Expedicao->possuiEmbalado() == true) {
                $this->_forward('tipo-conferencia','expedicao','mobile', array('placa' => $Expedicao->getPlaca()));
            }

            $this->view->volume = $this->_getParam('volume', null);
            $this->view->idTipoVolume = $this->_getParam('idTipoVolume', null);
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

    public function finalizadoAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao');
        $placa      = $this->_getParam('placa');

        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');

        if ($obrigaBiparEtiqueta == 'S') {
            $conferido = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
            if ($conferido > 0) {
                $result = $conferido;
            } else {
                $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO, "Array", $placa);
            }
        } else {
            $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
        }

        if (count($result) > 0) {
            $this->createXml('error', 'Faltam '.count($result).' produtos a serem conferidos');
        } else {
            $this->createXml('success','Todos os produtos já foram recebidos');
        }
    }

}

