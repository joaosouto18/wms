<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\MovimentacaoProduto,
    Core\Html2Pdf\HTML2PDF,
    Core\Html2Pdf\_class\exception\HTML2PDF_exception;

class Enderecamento_Relatorio_MovimentacaoProdutoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\MovimentacaoProduto\Filtro();
        $values = $form->getParams();

        if ($values)
        {
             $relatorio = new MovimentacaoProduto();
             $relatorio->init($values);
        }

        $this->view->form = $form;

    }

}