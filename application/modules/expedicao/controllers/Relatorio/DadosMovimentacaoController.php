<?php

class Expedicao_Relatorio_DadosMovimentacaoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $form = new \Wms\Module\Expedicao\Form\DadosMovimentacao();
        $params = $form->getParams();
        if ($params) {
            /** @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $HistoricoEstoqueRepository */
            $HistoricoEstoqueRepository   = $this->getEntityManager()->getRepository('wms:Enderecamento\HistoricoEstoque');
            $movimentacao = $HistoricoEstoqueRepository->getDadosMovimentacao($params);
            $this->exportCSV($movimentacao,"movimentacao",true);
            $form->populate($params);
        }
        $this->view->form = $form;
    }

}