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
                'style' => 'width:185px;'
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
            ->addDisplayGroup(array('etiquetaInicial','etiquetaFinal','pessoa','buscar'), 'identificacao', array('legend' => 'Vincular Etiqueta Separação'));

        $this->getElement('etiquetaInicial')->setAttrib('onkeydown','gotoFinal(event)');
        $this->getElement('etiquetaFinal')->setAttrib('onkeydown','gotoPessoa(event)');
        $this->getElement('pessoa')->setAttrib('onkeydown','gotoBuscar(event)');
    }
}