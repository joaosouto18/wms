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
                'class' => 'inptText'
            ))
            ->addElement('text', 'codMapaSeparacao', array(
                'size' => 15,
                'label' => 'Mapa Separação',
                'class' => 'inptText'
            ))
            ->addElement('text', 'rua', array(
                'size' => 5,
                'label' => 'Rua',
                'class' => 'inptText'
            ))
            ->addElement('button', 'salvarMapa', array(
                'label' => 'Buscar',
                'class' => 'btn btnSearch',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codMapaSeparacao','pessoa','rua','salvarMapa'), 'identificacao', array('legend' => 'Vincular Mapa Separação')
            );
    }

}