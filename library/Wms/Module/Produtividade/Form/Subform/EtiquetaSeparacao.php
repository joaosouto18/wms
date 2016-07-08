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
            ->addElement('text', 'pessoa', array(
                'size' => 15,
                'label' => utf8_encode('CPF Conferente'),
            ))
            ->addElement('text', 'etiquetaInicial', array(
                'size' => 10,
                'label' => 'Etiqueta Inicial',
            ))
            ->addElement('text', 'etiquetaFinal', array(
                'size' => 10,
                'label' => 'Etiqueta Final',
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Vincular',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('pessoa','etiquetaInicial','etiquetaFinal', 'submit'), 'identificacao', array('legend' => utf8_encode('Vincular Etiqueta Separação'))
            );
        $this->getElement('etiquetaInicial')->setAttrib('onkeydown','gotoFinal(event)');
        $this->getElement('etiquetaFinal')->setAttrib('onkeydown','gotoSubmit(event)');
    }
}

