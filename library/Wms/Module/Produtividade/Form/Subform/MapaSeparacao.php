<?php
namespace Wms\Module\Produtividade\Form\Subform;

use Core\Form\SubForm;

class MapaSeparacao extends SubForm
{

    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
            'onkeydown' => 'nextInput(event);'
        ))
            ->addElement('cpf', 'pessoa', array(
                'size' => 15,
                'label' => utf8_encode('CPF Conferente'),
            ))
            ->addElement('text', 'codMapaSeparacao', array(
                'size' => 15,
                'label' => utf8_encode('Mapa Separacao'),
            ))
            ->addElement('button', 'salvarMapa', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
                'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
            ))
            ->addDisplayGroup(array('codMapaSeparacao','pessoa','salvarMapa'), 'identificacao', array('legend' => utf8_encode('Vincular Mapa Separação'))
            );
    }

}