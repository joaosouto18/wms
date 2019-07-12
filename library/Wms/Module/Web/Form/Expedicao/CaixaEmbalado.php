<?php

namespace Wms\Module\Web\Form\Expedicao;

use Wms\Module\Web\Form;

class CaixaEmbalado extends Form
{
    public function init()
    {
        $this->setAttribs(array('id' => 'caixa-embalado-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('hidden', 'id')
            ->addElement('text', 'descricao', array(
                'label' => 'Descrição',
                'class' => 'caixa-alta focus',
                'size' => 40,
                'maxlength' => 100,
                'required' => true
            ))
            ->addElement('text', 'pesoMaximo', array(
                'label' => 'Peso Máximo',
                'alt' => 'centesimal',
                'required' => true,
                'size' => 15,
            ))
            ->addElement('text', 'cubagemMaxima', array(
                'label' => 'Cubagem Máxima',
                'alt' => 'centesimal',
                'required' => true,
                'size' => 15,
            ))
            ->addElement('text', 'mixMaximo', array(
                'label' => 'Mix Máximo',
                'alt' => 'number',
                'required' => true,
                'placeholder' => '0',
                'size' => 15,
            ))
            ->addElement('text', 'unidadesMaxima', array(
                'label' => 'Máximo de Unidades',
                'alt' => 'number',
                'required' => true,
                'placeholder' => '0',
                'size' => 15,
            ))
            ->addDisplayGroup(array('id', 'descricao', 'pesoMaximo', 'cubagemMaxima', 'mixMaximo', 'unidadesMaxima'), 'identificacao');
        $this->addSubFormTab('Cadastro de nova caixa', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Expedicao\CaixaEmbalado $caixaEmbalado)
    {
        $this->setDefaults($caixaEmbalado->toArray(true));
    }

}