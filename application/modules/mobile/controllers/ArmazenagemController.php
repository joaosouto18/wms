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
                'url' => 'enderecamento/leitura-picking' ,
                'label' => 'SELECIONAR PICKING',
            ),
            4 => array (
                'url' => 'ressuprimento/listar-picking',
                'label' => 'RESSUPRIMENTO PREVENTIVO',
            ),
            5 => array (
                'url' => 'enderecamento_automatico/lista-recebimento',
                'label' => 'ENDEREÇAMENTO AUTOMÁTICO',
            ),
            6 => array (
                'url' => 'enderecamento_manual' ,
                'label' => 'ENDEREÇAMENTO MANUAL',
            ),
            7 => array (
                'url' => 'enderecamento/movimentacao',
                'label' => 'TRANSFERÊNCIA DE ESTOQUE',
            ),

        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }
}