<?php
use Wms\Controller\Action;

class Mobile_ArmazenagemController  extends Action
{
    public function indexAction()
    {
        $menu = array(
            1 => array(
                'url' => 'enderecamento/ler-codigo-barras',
                'label' => 'ENDEREÇAMENTO',
            ),
            2 => array (
                'url' => 'enderecamento_automatico/lista-recebimento',
                'label' => 'ENDEREÇAMENTO AUTOMÁTICO',
            ),
            3 => array (
                'url' => 'enderecamento_manual' ,
                'label' => 'ENDEREÇAMENTO MANUAL',
            ),
            4 => array (
                'url' => 'enderecamento/movimentacao',
                'label' => 'TRANSFERÊNCIA DE ESTOQUE',
            )
        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }
}