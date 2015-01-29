<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;


class Imprimir extends Form
{
    public function init()
    {
        $em = $this->getEm();
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'imprimir'
        ));
            $this->addElement('text', 'rua', array(
                'size' => 10,
                'label' => 'Rua Inicial',
            ))
            ->addElement('text', 'ruafinal', array(
                'size' => 10,
                'label' => 'Rua Final',
            ))
            ->addElement('text', 'predio', array(
                'label' => 'Prédio Inicial',
                'size' => 10,
            ))
            ->addElement('text', 'prediofinal', array(
                'label' => 'Prédio Final',
                'size' => 10,
            ))
            ->addElement('text', 'apartamento', array(
                    'label' => 'Apartamento Inicial',
                    'size' => 10,
            ))
            ->addElement('text', 'apartamentofinal', array(
                'label' => 'Apartamento Final',
                'size' => 10,
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

            ->addDisplayGroup(array('rua', 'ruafinal', 'predio','prediofinal', 'nivel', 'nivelfinal','apartamento', 'apartamentofinal','lado','buscar'), 'identificacao', array('legend' => 'Busca')
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