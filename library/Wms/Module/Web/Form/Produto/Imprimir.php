<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;


class Imprimir extends Form
{
    public function init()
    {
        $arrQtdDigitos = Endereco::getQtdDigitos();
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'imprimir'
        ));
            $this->addElement('text', 'rua', array(
                'size' => 4,
                'maxlength' => $arrQtdDigitos['rua'],
                'label' => 'Rua Inicial',
            ))
            ->addElement('text', 'ruafinal', array(
                'size' => 4,
                'maxlength' => $arrQtdDigitos['rua'],
                'label' => 'Rua Final',
            ))
            ->addElement('text', 'predio', array(
                'label' => 'Prédio Inicial',
                'maxlength' => $arrQtdDigitos['predio'],
                'size' => 4,
            ))
            ->addElement('text', 'prediofinal', array(
                'label' => 'Prédio Final',
                'maxlength' => $arrQtdDigitos['predio'],
                'size' => 4,
            ))
            ->addElement('text', 'nivel', array(
                'label' => 'Nivel Inicial',
                'maxlength' => $arrQtdDigitos['nivel'],
                'size' => 4,
            ))
            ->addElement('text', 'nivelfinal', array(
                'label' => 'Nivel Final',
                'maxlength' => $arrQtdDigitos['nivel'],
                'size' => 4,
            ))
            ->addElement('text', 'apartamento', array(
                    'label' => 'Apto Inicial',
                'maxlength' => $arrQtdDigitos['apto'],
                    'size' => 4,
            ))
            ->addElement('text', 'apartamentofinal', array(
                'label' => 'Apto Final',
                'maxlength' => $arrQtdDigitos['apto'],
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

            ->addDisplayGroup(array('rua', 'ruafinal', 'predio','prediofinal', 'nivel', 'nivelfinal','apartamento', 'apartamentofinal', 'opcao','lado','buscar'), 'identificacao', array('legend' => 'Busca')
            );
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