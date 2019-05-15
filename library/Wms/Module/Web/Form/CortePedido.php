<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;

class CortePedido extends Form
{
    private $idExp;

    public function __construct($options = null, $idExp = null)
    {
        $this->idExp = $idExp;
        parent::__construct($options);
    }

    public function init()
    {
        //$pedidos = $this->getEm()->getRepository("wms:Expedicao")->getPedidosByExpedicao($this->idExp);

        $this
            ->addElement('text', 'codProduto', array(
                'label' => 'Cod. Produto',
                'class' => 'focus'
            ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ))
//            ->addElement('select', 'idPedido', array(
//                'mostrarSelecione' => true,
//                'class' => 'medio',
//                'label' => 'Pedido',
//                'multiOptions' => $pedidos
//            ))
            ->addElement('button', 'btnSubmit', array(
                'class' => 'btn',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ))
            ->addDisplayGroup(array('codProduto', 'grade', 'btnSubmit'), 'Buscar', array('legend' => 'Buscar por Produto'));
    }
}
