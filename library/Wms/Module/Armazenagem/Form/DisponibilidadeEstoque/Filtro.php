<?php

namespace Wms\Module\Armazenagem\Form\DisponibilidadeEstoque;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;

class Filtro extends Form {

    public function init()
    {
        $arrQtdDigitos = Endereco::getQtdDigitos();

        $this->setAttribs(array('id' => 'filtro-estoque-form',
                                'method' => 'post',
                                'class' => 'saveForm'));

        $this->addElement('text', 'rua', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'maxlength' => $arrQtdDigitos['rua'],
            'size' => 3,
            'label' => 'Rua',
        ))
        ->addElement('text', 'predio', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'maxlength' => $arrQtdDigitos['predio'],
            'size' => 3,
            'label' => 'Predio',
        ))
        ->addElement('text', 'nivel', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'maxlength' => $arrQtdDigitos['nivel'],
            'size' => 3,
            'label' => 'Nivel',
        ))
        ->addElement('text', 'apartamento', array(
            //'style' => 'width: 22px',
            'alt' => 'depositoEndereco',
            'maxlength' => $arrQtdDigitos['apartamento'],
            'size' => 3,
            'label' => 'Apartamento',
        ))
        ->addElement('checkbox', 'mostraOcupado', array(
                'label' => 'Exibir endereços ocupados',
                'checked' => false
        ))
        ->addElement('checkbox', 'mostrarPicking', array(
                'label' => 'Somente Picking',
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
            'mostrarPicking',
            'buscar',
            'imprimir'), 'endereco', array('legend' => 'Endereço'));
    }

}

