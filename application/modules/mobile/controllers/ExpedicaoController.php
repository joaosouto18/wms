<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Module\Mobile\Form\SenhaLiberacao,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Domain\Entity\Expedicao;

class Mobile_ExpedicaoController extends Action
{

    protected $bloquearOs = null;

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
        } else if ($result==0) {
            $this->addFlashMessage('success', 'Primeira Conferência finalizada com sucesso');
        } else {
            $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
        }
        $this->redirect('mobile/ordem-servico/conferencia-expedicao/idCentral/'.$central);

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
        $this->bloquearOs();
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
            $msg= 'Etiqueta '.$codigoBarras.' não encontrada';
            if ($this->bloquearOs=='S') {
                $this->createXml('error', 'Etiqueta '.$codigoBarras.' não encontrada');
            } else {
                $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                die();
            }

            return false;
        } else {
            if ($etiqueta[0]['codExpedicao'] != $idExpedicao) {
                $msg='Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'];

                if ($this->bloquearOs=='S') {
                    $this->bloqueioOs($idExpedicao, $msg, false);

                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                    die();
                }

                return false;
            }
        }

        //Se o tipo de conferencia for nao embalado, nao se pode bipar produtos que devem ser embalados
        if ($tipoConferencia == 'naoembalado' && $etiqueta[0]['embalado'] == 'S') {
            $msg='Produtos embalados devem ser vinculados a um patrimônio';
            if ($this->bloquearOs=='S'){
                $this->createXml('error', $msg);
            } else {
                $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                die();
            }
        }

        //Verifico se a etiqueta pertence a carga selecionada
        if  (!is_null($idTipoVolume) && !empty($idTipoVolume)) {
            if ($idTipoVolume != $etiqueta[0]['codCargaExterno']) {
                $msg='Etiqueta '.$codigoBarras.' não pertence a carga selecionada - Carga Correta:' . $etiqueta[0]['codCargaExterno'];

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                }  else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
        }

        //Etiqueta pertence a central Selecionada e a placa selecionada
        if  (!is_null($placa) && !empty($placa)) {
            if ($etiqueta[0]['pontoTransbordo'] != $idCentral) {
                $msg='Etiqueta não pertence a central ' . $idCentral;

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                }  else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
            if ($etiqueta[0]['placaCarga'] != $placa) {
                $msg='Etiqueta não pertence a placa ' . $placa;

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }

                return false;
            }
        } else {
            if ($etiqueta[0]['codEstoque'] != $idCentral) {
                $msg='Etiqueta não pertence a central ' . $idCentral;

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', 'Etiqueta não pertence a central ' . $idCentral, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                    }
                } else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
        }

        return $etiqueta;
    }

    public function validaStatusEtiqueta($idExpedicao, $status, $sessaoColetor,$etiqueta=null)
    {
        $this->bloquearOs();
        $tipoConferencia    = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);


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

                    $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

                    if ($verificaReconferencia=='S'){
                        $expedEntity=$this->_em->getReference('wms:Expedicao',$idExpedicao);
                        $statusExped=$expedEntity->getStatus()->getId();

                        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $etiquetaConfRepo */
                        $etiquetaConfRepo  = $this->em->getRepository('wms:Expedicao\EtiquetaConferencia');

                        if ( $statusExped==Expedicao::STATUS_PRIMEIRA_CONFERENCIA){

                            $resultado=$etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao,$etiqueta);

                            if ($resultado[0]['codStatus']==Expedicao::STATUS_PRIMEIRA_CONFERENCIA)
                                return false;
                        } else if ( $statusExped==Expedicao::STATUS_SEGUNDA_CONFERENCIA){

                            $resultado=$etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao,$etiqueta);

                            if ($resultado[0]['codStatus']==Expedicao::STATUS_SEGUNDA_CONFERENCIA)
                                return false;
                        }

                    } else {
                        return false;
                    }
                } else {
                    if ($obrigaRealizarRecebimento == 'S') {
                        $msg='Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido';

                        if ($this->bloquearOs=='S'){
                            $this->bloqueioOs($idExpedicao, $msg, false);
                            if ($this->_request->isXmlHttpRequest()) {
                                $this->createXml('error', $msg, $this->createUrlMobile());
                            } else {
                                $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                                die();
                            }
                        } else {
                            $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                            die();

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
        $this->bloquearOs();
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

        if ($this->bloquearOs!='S'){
            $this->createXml("error",$motivo,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/bloqueiaOS/1');
            die();
        }

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
    protected function confereEtiqueta($idEtiqueta, $volume = null,$idExpedicao=null)
    {
        $sessao = new \Zend_Session_Namespace('coletor');

        $date = new \DateTime();
        $date = $date->format('Y-m-d H:i:s');

        if (isset($sessao->parcialmenteFinalizado) && $sessao->parcialmenteFinalizado == true) {
            $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOSTransbordo = :osID , es.dataConferenciaTransbordo = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
            $q->setParameter('status', EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO);
        } else {
            $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

            if ($verificaReconferencia=='S'){

                $expedEntity=$this->_em->getReference('wms:Expedicao',$idExpedicao);
                $statusExped=$expedEntity->getStatus()->getId();
                if ( $statusExped==Expedicao::STATUS_PRIMEIRA_CONFERENCIA ){
                    $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaConferencia es set es.status = :status, es.codOsPrimeiraConferencia = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.codEtiquetaSeparacao = :idEtiqueta');
                    $q->setParameter('status', EtiquetaSeparacao::STATUS_PRIMEIRA_CONFERENCIA);
                } else {
                    $q = $this->_em->createQuery('update wms:Expedicao\EtiquetaConferencia es set es.status = :status, es.codOsPrimeiraConferencia = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.codEtiquetaSeparacao = :idEtiqueta');
                    $q->setParameter('status', EtiquetaSeparacao::STATUS_SEGUNDA_CONFERENCIA);
                }

                $q->setParameter('dataConferencia', $date);
                $q->setParameter('osID', $sessao->osID);
                $q->setParameter('idEtiqueta', $idEtiqueta);
                $q->setParameter('volumePatrimonio', $volume);
                $q->execute();

            }

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
        $this->bloquearOs();
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);

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
            $msg = "";
            if ($this->bloquearOs=='S'){
                return false;
            } else {
                $this->createXml("error","",'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
            }
        }

        $return = $this->validaStatusEtiqueta ($idExpedicao, $etiqueta[0]['codStatus'], $sessaoColetor, $etiquetaSeparacao);

        if ($return == false) {
            if ($etiqueta[0]['status'] == 'EXPEDIDO TRANSBORDO') {
                $this->_helper->messenger('info', 'Etiqueta de transbordo já conferida');
                $mensagem = 'Etiqueta de transbordo já conferida';
            } else {
                $this->_helper->messenger('info', 'ETIQUETA  com status '. $etiqueta[0]['status']);
                $mensagem = 'ETIQUETA com status '. $etiqueta[0]['status'];
            }

            $msg=$mensagem;
            if ($this->bloquearOs=='S'){
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error", $msg);
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
            } else {
                $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                die();
            }
            die();
            return false;
        }

        if ($sessaoColetor->parcialmenteFinalizado == true) {
            $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
            if ($obrigaBiparEtiqueta == 'N') {
                $this->confereEtiqueta($etiquetaSeparacao, $volume, $idExpedicao);
                $this->addFlashMessage('success', 'Produto conferido com sucesso');
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Produto conferido com sucesso');
                } else {
                    $this->_redirect('/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/Produto conferido com sucesso");
                }
            }
        }

        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto   = $LeituraColetor->analisarCodigoBarras($etiquetaProduto);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg='Produto '. $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] .' não confere com a etiqueta de separação ' . $etiquetaProduto;


                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, 'Produto '. $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] .' não confere com a etiqueta de separação ' . $etiquetaProduto, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml("error",$msg,$this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    $this->view->assign('bloqueiaOS', "1");
                    die();
                }
                return false;
            }
        }

        $this->confereEtiqueta($etiquetaSeparacao, $volume, $idExpedicao);

        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml('success', 'Etiqueta conferida com sucesso');
            die();
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

            $this->view->volume = $this->_getParam('volume', null);
            $this->view->idTipoVolume = $this->_getParam('idTipoVolume', null);
            $this->view->mensagem = $this->_getParam('msg', null);
            $this->view->placa = $Expedicao->getPlaca();
            $this->view->idExpedicao = $Expedicao->getIdExpedicao();

            $url="/volume".$this->_getParam('volume', null)."/volume".$this->_getParam('volume', null)."/placa".$this->_getParam('placa', null)."/bloqueiaOS".$this->_getParam('bloqueiaOS', null);

            if ( ($Expedicao->validacaoExpedicao() == false) || ( $Expedicao->osLiberada() == false)) {
                $this->mensagemColetor($Expedicao,$url);
            }

            if ($Expedicao->possuiEmbalado() == true) {
                $this->_forward('tipo-conferencia','expedicao','mobile', array('placa' => $Expedicao->getPlaca()));
            }



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
    public function mensagemColetor($Expedicao,$url="")
    {
        $this->_helper->messenger($Expedicao->getStatus(), $Expedicao->getMessage());
        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml($Expedicao->getRetorno(), $Expedicao->getMessage(), $Expedicao->getRedirect());
        } else {
            $redirect=$Expedicao->getRedirect();
            $this->_redirect($redirect);
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

    public function bloquearOs()
    {
        $this->bloquearOs = $this->getSystemParameterValue('BLOQUEIO_OS');

        return $this->bloquearOs;
    }

}

