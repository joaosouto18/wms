<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_EstoqueConsolidadoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\EstoqueConsolidado\Filtro();
        $values = $form->getParams();

        if ($values)
        {
            $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
            $estoqueDados = $EstoqueRepo->getEstoqueConsolidado($values);

            if(!empty($estoqueDados)) {
                if (isset($values['submit'])) {
                    $this->exportCSV($estoqueDados, 'Estoque_Consolidado', true);
                } else if (isset($values['exportPdf'])) {
                    $this->exportPDF($estoqueDados, 'estoqueConsolidado', 'RELATORIO DE ESTOQUE POR PRODUTO CONSOLIDADO', "L");
                }
            }
            else
                $this->addFlashMessage("error", "Nenhum registro encontrado com o(s) filtro(s) selecionado(s).");
        }

        $this->view->form = $form;

    }

}