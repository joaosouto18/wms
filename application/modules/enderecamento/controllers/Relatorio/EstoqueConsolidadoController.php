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
            $this->exportPDF($estoqueDados,'estoqueConsolidado','RELATÓRIO DE ESTOQUE POR PRODUTO CONSOLIDADO',"P");
        }

        $this->view->form = $form;

    }

}