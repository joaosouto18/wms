<?php

namespace Wms\Module\Armazenagem\Form\Movimentacao;

use Wms\Module\Web\Form;

class FiltroRelatorio extends Form
{

    public function init()
    {

        $this
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro-m',
                'id' => 'filtro-movimentacao',
            ))
            ->addElement('text', 'idProduto', array(
                'size' => 12,
                'label' => 'Cod. produto',
                'class' => 'focus',
            ))
            ->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ))
            ->addElement('text', 'rua', array(
                'size' => 3,
                'label' => 'Rua'
            ))
            ->addElement('text', 'predio', array(
                'size' => 3,
                'label' => 'Prédio',
            ))
            ->addElement('text', 'nivel', array(
                'size' => 3,
                'label' => 'Nível',
            ))
            ->addElement('text', 'apto', array(
                'size' => 3,
                'label' => 'Apto',
            ))
            ->addElement('submit', 'imprimir', array(
                'label' => 'Imprimir',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('idProduto', 'grade', 'rua', 'predio', 'nivel', 'apto', 'imprimir'), 'identificacao', array('legend' => 'Filtro')
            );

    }

}
