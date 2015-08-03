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
            $relatorio = new \Wms\Module\Enderecamento\Report\ProdutosSemPicking();
            $relatorio->init($values);

        }

        $this->view->form = $form;

    }

}