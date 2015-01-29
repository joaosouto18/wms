<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class Carregamento extends Form
{

    public function init()
    {
          $this
                ->setAttribs(array(
                    'method' => 'get',
                ))
                ->addElement('text', 'codExpedicao', array(
                    'size' => 10,
                    'label' => 'Código da Expedição',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('codExpedicao', 'submit'), 'identificacao', array('legend' => 'Expedição')
        );
    }

}