<?php

use Wms\Module\Web\Controller\Action;
use Wms\Module\Validade\Form\Validade as ValidadeForm;
use Wms\Module\Validade\Grid\Validade as ValidadeGrid;

class Validade_ConsultaController extends Action {

    public function indexAction() {
        ini_set('memory_limit', '-1');
        $params = $this->_getAllParams();
        $form = new ValidadeForm();
        if (!isset($params['dataReferencia']) || empty($params['dataReferencia'])) {
            $params['dataReferencia'] = date('d/m/Y');
        }
        $form->populate($params);
        $this->view->form = $form;

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $result = $produtoRepo->getProdutoByParametroVencimento($params);
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        foreach ($result as $key => $value) {
            $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['GRADE'], $value['QTD']);
            if(reset($vetEmbalagens) != null){
                $result[$key]['QTD_MAIOR'] = reset($vetEmbalagens);
            }else{
                $result[$key]['QTD_MAIOR'] = ' - ';
            }
            if(count($vetEmbalagens) > 1){
                $result[$key]['QTD_MENOR'] = end($vetEmbalagens);
            }else{
                $result[$key]['QTD_MENOR'] = ' - ';
            }
        }
        $grid = new ValidadeGrid();
        $this->view->grid = $grid->init($result);
        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            $pdfReport = new \Wms\Module\Validade\Report\ProdutosAVencer();
            $pdfReport->generatePDF($result, $params['dataReferencia']);
        }
        if (isset($params['gerarCsv']) && !empty($params['gerarCsv'])) {
            $this->exportCSV($result,'produtos_a_vencer',true);
        }
    }

}
