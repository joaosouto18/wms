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
            $this->exportCSV($estoqueDados, 'Estoque_Consolidado', true);
        }

        $this->view->form = $form;

    }

}