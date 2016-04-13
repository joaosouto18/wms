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
                'url' => 'enderecamento_reabastecimento-manual',
                'label' => 'REABASTECIMENTO MANUAL',
            ),
            3 => array (
                'url' => 'ressuprimento/listar-picking',
                'label' => 'RESSUPRIMENTO PREVENTIVO',
            ),
            4 => array (
                'url' => 'enderecamento_automatico/lista-recebimento',
                'label' => 'ENDEREÇAMENTO AUTOMÁTICO',
            ),
            5 => array (
                'url' => 'enderecamento_manual' ,
                'label' => 'ENDEREÇAMENTO MANUAL',
            ),
            6 => array (
                'url' => 'enderecamento/movimentacao',
                'label' => 'TRANSFERÊNCIA DE ESTOQUE',
            ),
             7 => array (
                'url' => 'enderecamento/leitura-picking' ,
                'label' => 'SELECIONAR PICKING',
            ),

        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }
}