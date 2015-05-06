<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\EstoqueReport;

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
        $idProduto = 10;

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
        $produtos = $estoqueRepo->getProdutosVolumesDivergentes($idProduto);


    }

}