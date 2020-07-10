<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;

class RelatorioCustomizado extends Form
{
    public function init($params = null)
    {
        $elements = array();
        if (isset($params) && ($params!= null)) {
            foreach ($params as $param) {
                $this->addElement($param['type'], $param['name'], array(
                    'label' => $param['label']
                ));
                $elements[] = $param['name'];
            }

        }

        $this->addElement('button', 'btnBuscar', array(
            'class' => 'btn',
            'label' => 'Buscar'
        ));
        $elements[] = 'btnBuscar';
        $this->addDisplayGroup($elements, 'filtro', array('legend' => 'Filtro'));
    }
}
