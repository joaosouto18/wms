<?php
use Wms\Controller\Action;

class Mobile_IndexController  extends Action
{
    public function indexAction()
    {
        $menu = array(
            1 => array(
                'url' => '/mobile/ordem-servico/conferencia-recebimento',
                'label' => 'CONF. RECEBIMENTO',
            ),
            2 => array(
                'url' => '/mobile/expedicao',
                'label' => 'EXPEDIÇÃO',
            ),
            3 => array(
                'url' => '/mobile/armazenagem',
                'label' => 'ARMAZENAGEM',
            ),
            4 => array(
                'url' => '/mobile/consulta-produto',
                'label' => 'CONSULTA PRODUTO',
            ),
            5 => array(
                'url' => '/mobile/ordem-servico/conferencia-inventario',
                'label' => 'INVENTÁRIO'
            )
        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function sucessoAction()
    {
        $link = '<a href="' . $this->view->url(array('controller' => 'index', 'action' => 'buscar-recebimento')) . '" target="_self" class="btn">Voltar</a>';
        $this->view->link = $link;
    }

}