<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\Pendencias as PendentesGrid,
    Wms\Module\Expedicao\Report\ProdutosSemConferencia as ProdutosSemConferenciaReport,
    Wms\Module\Web\Page;

class Expedicao_PendenciaController  extends Action
{
    public function indexAction() {
        $idExpedicao = $this->getRequest()->getParam('id');
        $placaCarga  = $this->getRequest()->getParam('placa');
        $transbordo  = $this->getRequest()->getParam('transbordo');
        $embalado    = $this->getRequest()->getParam('embalado');

        if (is_null($placaCarga))    {
            $status = "522,523";
            $expedicaoEn = $this->getEntityManager()->getRepository('wms:Expedicao')->findOneBy(array('id'=>$idExpedicao));
            if ($expedicaoEn->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
                $status = "522,523,526";
            }
        } else {
            $status = "522,523,526,532";
        }

        $GridCortes = new PendentesGrid();
        $this->view->gridCortes = $GridCortes->init($idExpedicao, $status, $placaCarga, $transbordo,"Etiquetas pendentes de conferência",$embalado)
            ->render();
    }

    public function recebimentoAction() {
        $idExpedicao = $this->getRequest()->getParam('id');

        $GridCortes = new PendentesGrid();
        $this->view->grid = $GridCortes->init($idExpedicao, "526", NULL, NULL, "Etiquetas pendentes de recebimento no ponto de transbordo")
            ->render();
    }

    public function viewAction () {
        $idExpedicao    = $this->getRequest()->getParam('id');
        $placaCarga  = $this->getRequest()->getParam('placa');
        $transbordo  = $this->getRequest()->getParam('transbordo');
        $embalado    = $this->getRequest()->getParam('embalado');

        if (is_null($placaCarga))    {
            $status = "522,523";
            $expedicaoEn = $this->getEntityManager()->getRepository('wms:Expedicao')->findOneBy(array('id'=>$idExpedicao));
            if ($expedicaoEn->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
                $status = "522,523,526";
            }
        } else {
            $status = "522,523,526,532";
        }

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,$status,"Array",$placaCarga,$transbordo,$embalado);

        $RelCarregamento    = new ProdutosSemConferenciaReport("L","mm","A4");
        $RelCarregamento->imprimir($idExpedicao, $result);
    }

    public function relatorioAction(){
        $idExpedicao    = $this->getRequest()->getParam('id');

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,"526","Array");

        $RelCarregamento    = new ProdutosSemConferenciaReport("L","mm","A4");
        $RelCarregamento->imprimir($idExpedicao, $result);

    }

}