<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class EquipeSeparacao extends Form
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