<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\EstoqueReport,
    Wms\Module\Enderecamento\Report\ProdutosVolumesDivergentes;

class Enderecamento_Relatorio_EstoqueProprietarioController extends Action
{
    public function indexAction(){
        $form = new \Wms\Module\Armazenagem\Form\EstoqueProprietario\FiltroRelatorio();
        $form->init($this->getSystemParameterValue("UTILIZA_GRADE"));
        $values = $form->getParams();
        if (isset($values['imprimir'])){
            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\EstoqueProprietario');
            $result = $estoqueRepo->getHistoricoEstoqueProprietario($values['codPessoa'], $values['idProduto'], $values['grade']);
            $this->exportPDF($result, 'HistoricoEstoqueProprietario.pdf', 'Relatório de Estoque Proprietário', 'P');
        }
        $this->view->form = $form;
    }
}