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
                    'label' => 'Expedição',
                ))
                ->addElement('text', 'codCarga', array(
                    'size' => 10,
                    'label' => 'Carga',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('codExpedicao','codCarga', 'submit'), 'identificacao', array('legend' => 'Expedição')
        );
    }

}