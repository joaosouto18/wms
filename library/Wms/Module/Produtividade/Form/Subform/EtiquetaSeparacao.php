<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 08/07/2016
 * Time: 13:49
 */

namespace Wms\Module\Produtividade\Form\Subform;

use Core\Form\SubForm;

class EtiquetaSeparacao extends SubForm
{
    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
        ))
            ->addElement('cpf', 'pessoa', array(
                'size' => 15,
                'label' => utf8_encode('CPF Conferente'),
            ))
            ->addElement('text', 'etiquetaInicial', array(
                'size' => 15,
                'label' => 'Etiqueta Inicial',
            ))
            ->addElement('text', 'etiquetaFinal', array(
                'size' => 15,
                'label' => 'Etiqueta Final',
            ))
            ->addElement('button', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
                'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Vincular',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('etiquetaInicial','etiquetaFinal','pessoa','buscar','submit'), 'identificacao', array('legend' => 'Vincular Etiqueta Separação'));

    }
}

