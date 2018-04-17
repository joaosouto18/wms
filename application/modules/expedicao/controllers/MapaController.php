<?php
use Wms\Module\Web\Controller\Action;
;

class Expedicao_MapaController  extends Action
{

    public function consultarAction()
    {
        $idExpedicao = $this->_getParam('id');
        $idMapa = $this->_getParam('COD_MAPA_SEPARACAO');
        $grid = new \Wms\Module\Web\Grid\Expedicao\ProdutosMapa();
        $this->view->grid = $grid->init($idMapa,$idExpedicao)->render();
    }

    public function conferenciaAction(){
        $idMapa = $this->_getParam('COD_MAPA_SEPARACAO');
        $idProduto = $this->_getParam('COD_PRODUTO');
        $grade = $this->_getParam('DSC_GRADE');
        $nomConferencia = $this->_getParam('NUM_CONFERENCIA');
        $grid = new \Wms\Module\Web\Grid\Expedicao\ConferenciaProdutoMapa();
        $this->view->grid = $grid->init($idMapa,$idProduto,$grade,$nomConferencia);
    }

    public function pendentesConferenciaAction()
    {
        $this->view->idMapa = $idMapa = $this->_getParam('COD_MAPA_SEPARACAO');

        $grid = new \Wms\Module\Expedicao\Grid\MapasPendentes();
        $this->view->grid = $grid->init($idMapa)->render();
    }

    public function imprimirAjaxAction()
    {
        $idMapa = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferirByMapa($idMapa);

        $this->exportPDF($result, 'Produtos_Sem_conferencia_Mapa', 'Produtos nao conferidos do Mapa ' . $idMapa, 'L');
    }

    public function relatorioPendentesAjaxAction()
    {
        $idExpedicao = $this->getRequest()->getParam('id');
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferirByExpedicao($idExpedicao);

        $RelatorioPendencias = new \Wms\Module\Expedicao\Report\MapasSemConferencia("L", "mm", "A4");
        $RelatorioPendencias->imprimir($idExpedicao, $result, $embalagemRepo);

    }

    public function desfazerConferenciaAjaxAction()
    {
        $params = $this->_getAllParams();
        $codMapaSeparacao = $params['COD_MAPA_SEPARACAO'];
        $codProduto = $params['COD_PRODUTO'];
        $grade = $params['DSC_GRADE'];
        try {
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
            $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
            $array = $mapaSeparacaoConferenciaRepo->removeMapaSeparacaConferencia($params);

            $this->_helper->messenger('success', "Conferência do produto $codProduto grade $grade com quantidade de $array[quantidade] no mapa de separação $codMapaSeparacao foi reiniciada");
            return $this->redirect('index','os','expedicao', array('id' => $array['expedicao']));

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function relatorioItensVolumeEmbaladoAction()
    {
        $idExpedicao = $this->_getParam('id');
        $reportPDF = new \Wms\Module\Expedicao\Report\VolumeEmbalado();

        $reportPDF->imprimir($idExpedicao);
    }
}