<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;

class CortePedido extends Form
{
    public function init()
    {
        $this
            ->addElement('text', 'codProduto', array(
                'label' => 'Cod. Produto',
                'class' => 'focus'
            ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ))
            ->addElement('checkbox', 'quebraEndereco', array(
                'label' => 'Quebrar por endereços (Apenas se tiver mapas)',
                'checkedValue' => 'true'
            ))
            ->addElement('button', 'btnSubmitPedido', array(
                'class' => 'btn',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ))
            ->addDisplayGroup(array('codProduto', 'grade', 'quebraEndereco', 'btnSubmitPedido'), 'Buscar', array('legend' => 'Buscar por Produto'));
    }
}
