<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;


class Imprimir extends Form
{
    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'imprimir'
        ));
        $this->addElement('text', 'ruaInicial', array(
                'size' => 4,
                'alt' => 'enderecoRua',
                'label' => 'Rua inicial',
            ))
            ->addElement('text', 'ruaFinal', array(
                'size' => 4,
                'alt' => 'enderecoRua',
                'label' => 'Rua final',
            ))
            ->addElement('text', 'predioInicial', array(
                'label' => 'Predio inicial',
                'alt' => 'enderecoPredio',
                'size' => 4,
            ))
            ->addElement('text', 'predioFinal', array(
                'label' => 'Predio final',
                'alt' => 'enderecoPredio',
                'size' => 4,
            ))
            ->addElement('text', 'nivelInicial', array(
                'label' => 'Nivel Inicial',
                'alt' => 'enderecoNivel',
                'size' => 4,
            ))
            ->addElement('text', 'nivelFinal', array(
                'label' => 'Nivel final',
                'alt' => 'enderecoNivel',
                'size' => 4,
            ))
            ->addElement('text', 'aptoInicial', array(
                'label' => 'Apto inicial',
                'alt' => 'enderecoApartamento',
                'size' => 4,
            ))
            ->addElement('text', 'aptoFinal', array(
                'label' => 'Apto final',
                'alt' => 'enderecoApartamento',
                'size' => 4,
            ))
            ->addElement('submit', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'lado', array(
                'label' => 'Lado',
                'mostrarSelecione' => false,
                'multiOptions' => EnderecoEntity::$listaTipoLado,
                'class' => 'pequeno',
            ))
            ->addElement('select', 'tipoEndereco', array(
                'label' => 'Tipo',
                'mostrarSelecione' => false,
                'multiOptions' => EnderecoEntity::$tiposEndereco,
                'class' => 'pequeno',
            ))
            ->addElement('select', 'opcao', array(
                'label' => 'Selecione',
                'mostrarSelecione' => false,
                'multiOptions' => array(
                    'todos' => 'Todos os endereços',
                    'sem' => 'Sem produtos',
                    'com' => 'Com produtos'
                ),
                'class' => 'pequeno',
            ))

            ->addDisplayGroup(array('ruaInicial', 'ruaFinal', 'predioInicial', 'predioFinal', 'nivelInicial', 'nivelFinal', 'aptoInicial', 'aptoFinal'), 'intervalo', array('legend' => 'Intervalo de Endereços'))
            ->addDisplayGroup(array('opcao', 'lado', 'tipoEndereco','buscar'), 'caracteristica', array('legend' => 'Características'));

    }

    /**
     *
     * @param array $params
     * @return boolean
     */
    public function isValid($params)
    {
        extract($params);

        if (!parent::isValid($params))
            return false;

        if ($this->checkAllEmpty())
            return false;

        return true;
    }

}