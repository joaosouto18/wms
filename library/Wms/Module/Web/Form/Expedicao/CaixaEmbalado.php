<?php

namespace Wms\Module\Web\Form\Expedicao;

use Wms\Module\Web\Form;

class CaixaEmbalado extends Form
{
    public function init()
    {
        $this->setAttribs(array('id' => 'caixa-embalado-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'id', array(
            'label' => 'Código',
            'class' => 'codigo pequeno',
            'readonly' => true,
            'disable' => true
        ))
            ->addElement('text', 'dscMotivo', array(
                'label' => 'Descrição',
                'class' => 'caixa-alta focus',
                'size' => 60,
                'maxlength' => 60,
                'required' => true
            ))
            ->addElement('text', 'codExterno', array(
                'label' => 'Código Externo',
                'class' => 'caixa-alta focus',
                'size' => 20,
                'maxlength' => 20
            ))
            ->addDisplayGroup(array('id', 'dscMotivo', 'codExterno'), 'identificacao');
        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Expedicao\CaixaEmbalado $motivoCorte)
    {
        $values = array(
            'identificacao' => array(
                'id' => $motivoCorte->getId(),
                'dscMotivo' => $motivoCorte->getDscMotivo(),
                'codExterno' => $motivoCorte->getCodExterno()
            )
        );

        $this->setDefaults($values);
    }

}