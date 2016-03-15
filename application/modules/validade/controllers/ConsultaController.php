<?php

use Wms\Module\Web\Controller\Action;
use Wms\Module\Validade\Form\Index as ValidadeForm;
use Wms\Module\Validade\Grid\Consulta as ConsultaGrid;

class Validade_ConsultaController extends Action
{
    public function indexAction()
    {
        $params = $this->_getAllParams();
        $form = new ValidadeForm();
        $this->view->form = $form;

        $grid = new ConsultaGrid();
        $this->view->grid = $grid->init($params);

        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $produto = $produtoRepo->getProdutoByParametroVencimento($params);

            $this->exportPDF($produto,'Produtos a Vencer.pdf', 'PRODUTOS A VENCER', 'P');
        }

    }

}