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
}