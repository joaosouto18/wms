<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 20/07/2016
 * Time: 15:18
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
                'label' => 'CPF Conferente',
                'style' => 'width:190px;',
                'class' => 'inptText',
            ))
            ->addElement('text', 'etiquetaInicial', array(
                'size' => 12,
                'label' => 'Etiqueta Inicial',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text', 'etiquetaFinal', array(
                'size' => 12,
                'label' => 'Etiqueta Final',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text','showIntervalo', array(
                'label' => 'Intervalo',
                'class' => 'inptText',
                'id' => 'txtIntervalo',
                'size' => 3,
                'readonly' => true,
                'disabled' => true,
            ))
            ->addElement('button', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn btnSearch',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('etiquetaInicial','etiquetaFinal','showIntervalo','pessoa','buscar'), 'identificacao', array('legend' => 'Vincular Etiqueta Separação'));

        $this->getElement('etiquetaInicial')->setAttrib('onkeydown','gotoFinal(event)');
        $this->getElement('etiquetaFinal')->setAttrib('onkeydown','gotoPessoa(event)');
        $this->getElement('pessoa')->setAttrib('onkeydown','gotoBuscar(event)');
    }
}