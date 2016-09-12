<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Validade\Form\Validade as ValidadeForm;
use Wms\Module\Validade\Grid\Validade as ValidadeGrid;

class Validade_ConsultaController extends Action
{
    public function indexAction()
    {
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

        $grid = new ValidadeGrid();
        $this->view->grid = $grid->init($result);

        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            $dataFormatada = str_replace('/','-',$params['dataReferencia']);
            $this->exportPDF($result,'Produtos vencidos ou à vencer '.$dataFormatada.'.pdf', 'Produtos vencidos ou a vencer até '.$params['dataReferencia'] , 'L');
        }
    }
}