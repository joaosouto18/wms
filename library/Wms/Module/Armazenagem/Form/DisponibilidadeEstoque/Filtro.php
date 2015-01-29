<?php

namespace Wms\Module\Armazenagem\Form\DisponibilidadeEstoque;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;

class Filtro extends Form {

    public function init()
    {

        $this->setAttribs(array('id' => 'filtro-estoque-form',
                                'method' => 'post',
                                'class' => 'saveForm'));

        $this->addElement('text', 'rua', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'size' => 3,
            'label' => 'Rua',
        ))
        ->addElement('text', 'predio', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'size' => 3,
            'label' => 'Predio',
        ))
        ->addElement('text', 'nivel', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'size' => 3,
            'label' => 'Nivel',
        ))
        ->addElement('text', 'apartamento', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'size' => 3,
            'label' => 'Apartamento',
        ))
        ->addElement('checkbox', 'mostraOcupado', array(
                'label' => 'Exibir endereços ocupados',
                'checked' => false
        ))
        ->addElement('submit', 'imprimir', array(
                'class' => 'btn',
                'label' => 'Imprimir',
                'decorators' => array('ViewHelper'),
        ))
        ->addElement('submit', 'buscar', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ))
        ->addDisplayGroup(array(
            'rua',
            'predio',
            'nivel',
            'apartamento',
            'mostraOcupado',
            'buscar',
            'imprimir'), 'endereco', array('legend' => 'Endereço'));
    }

}

