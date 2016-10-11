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
            ->addElement('button', 'btnSubmit', array(
                    'class' => 'btn',
                    'label' => 'Buscar',
//                    'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codProduto', 'grade', 'btnSubmit'), 'Buscar', array('legend' => 'Buscar por Produto'));
        
    }

}
