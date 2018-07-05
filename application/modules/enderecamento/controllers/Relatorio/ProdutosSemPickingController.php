<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_ProdutosSemPickingController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\EstoqueConsolidado\Filtro();
        $values = $form->getParams();

        if ($values)
        {
            if (isset($values['submit'])) {
                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepo */
                $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
                $estoqueReport = $EstoqueRepo->getEstoqueProdutosSemPicking($values);
                $this->exportCSV($estoqueReport, 'produtos_sem_picking');
            }
            $relatorio = new \Wms\Module\Enderecamento\Report\ProdutosSemPicking();
            $relatorio->init($values);

        }

        $this->view->form = $form;

    }

}