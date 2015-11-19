<?php

namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form;

use Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;

class FiltroImpressao extends Form
{

    public function init()
    {

        $em = $this->getEm();

        //form's attr
        $this->setAttribs(array('id' => 'filtro-impressao-form', 'class' => 'saveForm'))
            ->setMethod('get');


        $formIdentificacao = new SubForm;

        //endereço
        $formIdentificacao->addElement('text', 'inicialRua', array(
            'size' => 3,
            'alt' => 'depositoEndereco',
            'decorators' => array('ViewHelper'),
            'title' => 'Obrigatório.',
        ))
            ->addElement('text', 'finalRua', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialPredio', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalPredio', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialNivel', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalNivel', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'inicialApartamento', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('text', 'finalApartamento', array(
                'size' => 3,
                'alt' => 'depositoEndereco',
                'decorators' => array('ViewHelper'),
                'title' => 'Obrigatório.',
            ))
            ->addElement('select', 'lado', array(
                'mostrarSelecione' => false,
                'multiOptions' => EnderecoEntity::$listaTipoLado,
                'decorators' => array('ViewHelper'),
                'class' => 'pequeno',
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
