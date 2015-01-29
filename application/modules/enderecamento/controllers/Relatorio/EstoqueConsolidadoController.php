<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\EstoqueConsolidado;

class Enderecamento_Relatorio_EstoqueConsolidadoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\EstoqueConsolidado\Filtro();
        $values = $form->getParams();

        if ($values)
        {
            $relatorio = new \Wms\Module\Enderecamento\Report\EstoqueConsolidado();
            $relatorio->init($values);

        }

        $this->view->form = $form;

    }

}