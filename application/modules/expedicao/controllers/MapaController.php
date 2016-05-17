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
        $idExpedicao = $this->_getParam('id');
        $this->view->idMapa = $idMapa = $this->_getParam('COD_MAPA_SEPARACAO');

        $grid = new \Wms\Module\Expedicao\Grid\MapasPendentes();
        $this->view->grid = $grid->init($idMapa)->render();
    }

    public function imprimirAjaxAction()
    {
        $idMapa = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferir($idMapa);

        $this->exportPDF($result, 'Produtos_Sem_conferencia_Mapa', 'Produtos nao conferidos do Mapa ' . $idMapa, 'L');
    }
}