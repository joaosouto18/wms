<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_PickingMultiplosProdutosController extends Action
{
    public function indexAction()
    {
            $relatorio = new \Wms\Module\Enderecamento\Report\PickingMultiplosProdutos();
            $relatorio->init();
    }

}