<?php

namespace Wms\Module\Armazenagem\Form\Movimentacao;

use Wms\Module\Web\Form;

class TransferenciaEndereco extends Form
{

    public function init()
    {

        $this
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro',
                'id' => 'transferencia-endereco',
            ))
            ->addElement('text', 'uma', array(
                'size' => 10,
                'label' => 'U.M.A',
            ))
            ->addElement('text', 'endereco', array(
                'label' => 'Novo Endereço',
                'alt' => 'endereco',
                'placeholder' => '00.000.00.00'
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Alterar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('uma', 'endereco', 'submit'), 'identificacao', array('legend' => '')

            );

    }

}
