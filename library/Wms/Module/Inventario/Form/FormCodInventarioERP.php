<?php

namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form;

class FormCodInventarioERP extends Form
{

    public function init()
    {

        $this->setAttribs(array(
            'id' => 'form-cod-inventario-erp',
            'method' => 'post',
            'action' => 'inventario\index\view-vincular-cod-erp-ajax'
        ));

        $this->addElement('text', 'id', array(
                'label' => 'Inventário no WMS',
                'readonly' => true
            ))
            ->addElement('numeric', 'codInventarioErp', array(
                'label' => 'Código deste Inventário no ERP',
                'required' => true
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Vincular',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('id', 'codInventarioErp','submit'), 'Vinculo', array('legend' => 'Vincular no inventário seu respectivo código no ERP'));

    }

}
