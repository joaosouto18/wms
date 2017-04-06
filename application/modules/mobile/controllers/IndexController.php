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
                'url' => '/mobile/ordem-servico/seleciona-filial',
                'label' => 'EXPEDIÇÃO',
            ),
            3 => array(
                'url' => '/mobile/armazenagem',
                'label' => 'ARMAZENAGEM',
            ),
            4 => array(
                'url' => '/mobile/ressuprimento',
                'label' => 'RESSUPRIMENTO',
            ),
            5 => array(
                'url' => '/mobile/ordem-servico/conferencia-inventario',
                'label' => 'INVENTÁRIO'
            ),
            6 => array(
                'url' => '/mobile/consulta-produto',
                'label' => 'CONSULTA PRODUTO',
            ),
            7 => array(
                'url' => '/mobile/reentrega/recebimento',
                'label' => 'REENTREGA',
            ),
            8 => array(
                'url' => '/mobile/recebimento-transbordo/produtividade',
                'label' => 'PRODUTIVIDADE',
            ),
//            9 => array(
//                'url' => '/mobile/enderecamento/cadastro-produto-endereco',
//                'label' => 'CADASTRO PRODUTO ENDERECO'
//            ),
            9 => array(
                'url' => '/mobile/consulta-endereco',
                'label' => 'CONSULTA ENDEREÇO'
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