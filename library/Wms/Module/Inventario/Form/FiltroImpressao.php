<?php

namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form;

use Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;

class FiltroImpressao extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'filtro-impressao-form', 'class' => 'saveForm'))
            ->setMethod('get');

        $formIdentificacao = new SubForm;

        //endereço
        $formIdentificacao->addElement('text', 'inicialRua', array(
            'size' => 3,
            'alt' => 'enderecoRua',
            'decorators' => array('ViewHelper'),
            'title' => 'Obrigatório.',
        ))
            ->addElement('text', 'finalRua', array(
                'size' => 3,
                'alt' => 'enderecoRua',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('select', 'lado', array(
                'mostrarSelecione' => false,
                'multiOptions' => EnderecoEntity::$listaTipoLado,
                'decorators' => array('ViewHelper'),
                'class' => 'pequeno',
            ))
            ->addElement('select', 'status', array(
                'mostrarSelecione' => false,
                'multiOptions' => array(
                    '1' => 'A inventariar',
                    '2' => 'Inventariados',
                ),
                'value' => '1',
                'decorators' => array('ViewHelper')
            ))
            ->addElement('button', 'btnBuscar', array(
                'label' => 'Buscar',
                'attribs' => array(
                    'id' => 'btn-imprimir-enderecos'
                )
            ));

        $formIdentificacao->addDisplayGroup(array(
            'inicialRua',
            'finalRua',
            'inicialPredio',
            'finalPredio',
            'inicialNivel',
            'finalNivel',
            'inicialApartamento',
            'finalApartamento',
            'btnBuscar'
        ), 'endereco', array('legend' => 'Busca'));

        $this->addSubFormTab('Busca', $formIdentificacao, 'identificacao', 'forms/filtro-impressao.phtml');
    }

}
