<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\MovimentacaoProduto;

class Enderecamento_Relatorio_MovimentacaoProdutoController extends Action
{
    public function indexAction()
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form = new \Wms\Module\Armazenagem\Form\MovimentacaoProduto\Filtro();
        $form->init($utilizaGrade);
        $values = $form->getParams();

        if ($values)
        {
             $relatorio = new MovimentacaoProduto();
             $relatorio->init($values);
        }

        $this->view->form = $form;

    }

}