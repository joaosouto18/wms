<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_PickingMultiplosProdutosController extends Action
{
    public function indexAction()
    {
        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\Filtro();
        $form->init(false);
        $values = $form->getParams();

        if ($values)
        {
            $relatorio = new \Wms\Module\Enderecamento\Report\PickingMultiplosProdutos();
            $relatorio->init($values);
        }
        $this->view->form = $form;
    }

    public function imprimirAction() {
    }

}