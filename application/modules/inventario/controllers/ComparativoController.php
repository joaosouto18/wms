<?php

class Inventario_ComparativoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $params = $this->_getAllParams();
        $form = new \Wms\Module\Inventario\Form\FormComparativo();
        $estoqueErpRepo = $this->_em->getRepository("wms:Enderecamento\EstoqueErp");

        $form->populate($params);
        $this->view->form = $form;

        $idInventario = null;
        if (isset($params['inventario'])&& ($params['inventario'] != null)) {
            $idInventario = $params['inventario'];
        }

        $result = $estoqueErpRepo->getProdutosDivergentesByInventario($idInventario);
        $grid = new \Wms\Module\Inventario\Grid\ComparativoEstoque();
        $this->view->grid = $grid->init($result);

            if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
                $pdf = array();
                foreach ($result as $line) {
                    $pdf[] = array(
                        'Código'=>$line['COD_PRODUTO'],
                        'Grade'=>$line['DSC_GRADE'],
                        'Produto'=>$line['DSC_PRODUTO'],
                        'Estoque WMS'=> $line['ESTOQUE_WMS'],
                        'Estoque ERP'=> $line['ESTOQUE_ERP']);
                }
                $this->exportPDF($pdf,"comparativoEstoque","Comparativo de Estoque","P");
            }

    }
}