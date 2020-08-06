<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_ComparativoInventarioController  extends Action
{

    public function indexAction()
    {

        $invERP = array();
        $invERP[] = array('COD_PRODUTO' =>'1010','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1010');
        $invERP[] = array('COD_PRODUTO' =>'2020','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 2020');
        $invERP[] = array('COD_PRODUTO' =>'1013','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');

        $invWMS = array();
        $invWMS[] = array('COD_PRODUTO' => '1010', 'QTD' => '5','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1010');
        $invWMS[] = array('COD_PRODUTO' => '1013', 'QTD' => '3','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');
        $invWMS[] = array('COD_PRODUTO' => '2021', 'QTD' => '7','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 2021');

        $invWMSERP = array();
        $invWMSERP[] = array('COD_PRODUTO' => '1010', 'QTD' => '5','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1010');
        $invWMSERP[] = array('COD_PRODUTO' => '1013', 'QTD' => '3','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');

        $invApenasERP = array();
        $invApenasERP[] = array('COD_PRODUTO' => '2020','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 2020');

        $invApenasWMS = array();
        $invApenasWMS[] = array('COD_PRODUTO' => '2021', 'QTD' => '7','DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 2021');

        $result = array(
            'resultado-inventario' => $invWMS,
            'inventario-erp' => $invERP,
            'inventario-erp-wms' => $invWMSERP,
            'apenas-wms' => $invApenasWMS,
            'apenas-erp' => $invApenasERP
        );

        $params = $this->getRequest()->getParams();

        $filtroForm = new \Wms\Module\InventarioNovo\Form\ComparativoInventarioForm();
        if (isset($params['btnSubmit'])) {
            $filtroForm->init(true);
            $resultForm = new \Wms\Module\InventarioNovo\Form\ResultadoComparativoInventarioForm();
            $resultForm->setDefaultsGrid($result);
            $this->view->resultadoForm = $resultForm;
        }

        $filtroForm->setDefaults($params);
        $this->view->form = $filtroForm;

    }
}