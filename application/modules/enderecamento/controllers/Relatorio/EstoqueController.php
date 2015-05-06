<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\EstoqueReport,
    Wms\Module\Enderecamento\Report\ProdutosVolumesDivergentes;

class Enderecamento_Relatorio_EstoqueController extends Action
{
    public function indexAction()
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form = new \Wms\Module\Armazenagem\Form\Movimentacao\FiltroRelatorio();
        $form->init($utilizaGrade);
        $values = $form->getParams();

        if ($values)
        {
            $relatorio = new EstoqueReport();
            $relatorio->init($values);
        }

        $this->view->form = $form;

    }

    public function consultarProdutoAction()
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
        $result = $estoqueRepo->getProdutosVolumesDivergentes();

        $this->exportPDF($result, 'ProdutosVolumesDivergentes.pdf', 'Relatório de Produtos com Volumes Divergentes no Estoque', 'P');
    }

}