<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class EquipeSeparacaoMapa extends Form
{

    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
        ))
            ->addElement('text', 'pessoa', array(
                'size' => 15,
                'label' => utf8_encode('Matrícula Conferente'),
            ))
            ->addElement('text', 'codMapaSeparacao', array(
                'size' => 12,
                'label' => utf8_encode('Mapa Separacao'),
            ))
            ->addElement('submit', 'salvarMapa', array(
                'label' => 'Vincular',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('pessoa','codMapaSeparacao','salvarMapa'), 'identificacao', array('legend' => utf8_encode('Vincular Mapa Separação'))
            );
    }

}