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
                'label' => 'CPF Funcionário',
                'class' => 'inptText'
            ))
            ->addElement('text', 'codMapaSeparacao', array(
                'size' => 15,
                'label' => 'Mapa Separação',
                'class' => 'inptText'
            ))
            ->addElement('button', 'salvarMapa', array(
                'label' => 'Buscar',
                'class' => 'btn btnSearch',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codMapaSeparacao','pessoa','salvarMapa'), 'identificacao', array('legend' => 'Vincular Mapa Separação')
            );
    }

}