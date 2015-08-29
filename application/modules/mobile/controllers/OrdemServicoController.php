<?php

use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao;

class Mobile_OrdemServicoController extends Action
{
    public function conferenciaRecebimentoAction()
    {
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

        $this->view->recebimentoIniciado = $recebimentoRepo->buscarStatusIniciado();
        $this->view->recebimentoEmConferencia = $recebimentoRepo->buscarStatusEmConferenciaColetor();
    }

    public function centraisEntregaAction()
    {
        $carregamento = $this->_getParam('carregamento', null);
        $transbordo = $this->_getParam('transbordo', null);

        $action = 'conferencia-expedicao';
        if ($transbordo) {$action = 'conferencia-transbordo';}
        if ($carregamento) {$action = 'equipe-carregamento';}

        $this->view->action = $action;
        $sessao = new \Zend_Session_Namespace('deposito');

        if (count($sessao->centraisPermitidas) == 1) {
            $this->redirect($action, 'ordem-servico');
        }
        $this->view->centraisPermitidas = $sessao->centraisPermitidas;
    }

    public function conferenciaExpedicaoAction()
    {
        $idCentral = $this->getIdCentral();

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $this->view->iniciadas  = $expedicaoRepo->getOSByUser();

        $status = array(Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_EM_CONFERENCIA, Expedicao::STATUS_PARCIALMENTE_FINALIZADO, Expedicao::STATUS_PRIMEIRA_CONFERENCIA, Expedicao::STATUS_SEGUNDA_CONFERENCIA);
        $this->view->reconfere = $this->getSystemParameterValue("RECONFERENCIA_EXPEDICAO");
        $this->view->expedicoes = $expedicaoRepo->getByStatusAndCentral($status, $idCentral);
    }

    public function equipeCarregamentoAction()
    {
        $idCentral = $this->getIdCentral();

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $this->view->iniciadas  = $expedicaoRepo->getOSByUser();

        $status = array(Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_EM_CONFERENCIA, Expedicao::STATUS_PARCIALMENTE_FINALIZADO, Expedicao::STATUS_PRIMEIRA_CONFERENCIA, Expedicao::STATUS_SEGUNDA_CONFERENCIA);
        $this->view->reconfere = $this->getSystemParameterValue("RECONFERENCIA_EXPEDICAO");
        $this->view->expedicoes = $expedicaoRepo->getByStatusAndCentral($status, $idCentral);
    }


    public function conferenciaTransbordoAction()
    {
        $idCentral = $this->getIdCentral();

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $this->view->iniciadas  = $expedicaoRepo->getOSByUser();
        $this->view->expedicoes = $expedicaoRepo->getByStatusAndCentral(Expedicao::STATUS_PARCIALMENTE_FINALIZADO, $idCentral);
    }

    public function recebimentoTransbordoAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');

        $this->view->iniciadas  = $expedicaoRepo->getOSByUser();
        $this->view->expedicoes = $expedicaoRepo->getByStatusAndCentral(Expedicao::STATUS_PARCIALMENTE_FINALIZADO);
    }

    /**
     * @return mixed
     */
    public function getIdCentral()
    {
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $sessaoColetor->parcialmenteFinalizado = false;

        $idCentral = $this->getRequest()->getParam('idCentral');
        if (empty($idCentral)) {
            $sessao = new \Zend_Session_Namespace('deposito');
            $idCentral = $sessao->centraisPermitidas;
            $sessaoColetor->centralSelecionada = $idCentral[0];
        } else {
            $sessaoColetor->centralSelecionada = $idCentral;
        }

        /** @var \Wms\Domain\Entity\Filial $filialEn */
        $filialRepo = $this->em->getRepository('wms:Filial');
        $filialEn = $filialRepo->findOneBy(array('codExterno' => $idCentral));

        if ($filialEn) {
            $sessaoColetor->ObrigaBiparEtiquetaProduto = $filialEn->getIndLeitEtqProdTransbObg();
            $sessaoColetor->RecebimentoTransbordoObrigatorio = $filialEn->getIndRecTransbObg();
            return $idCentral;
        }
        return $idCentral;
    }

    public function conferenciaInventarioAction()
    {
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository('wms:Inventario');
        $this->view->inventarios = $inventarioRepo->getByStatus(\Wms\Domain\Entity\Inventario::STATUS_LIBERADO);
    }

}

