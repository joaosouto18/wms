<?php

class Expedicao_Relatorio_DadosProdutosController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $form = new \Wms\Module\Expedicao\Form\DadosProdutos();
        $params = $form->getParams();
        if ($params) {
            /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
            $ProdutoRepository   = $this->getEntityManager()->getRepository('wms:Produto');
            $produtos = $ProdutoRepository->getDadosProdutos($params);
            $this->exportCSV($produtos,'produtos',true);
            $form->populate($params);
        }
        $this->view->form = $form;
    }
}