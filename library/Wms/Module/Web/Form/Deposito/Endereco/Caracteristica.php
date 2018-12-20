<?php

namespace Wms\Module\Web\Form\Deposito\Endereco;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Caracteristica extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-caracteristica-form', 'class' => 'saveForm'));

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'id', array(
            'label' => 'Código Interno',
            'class' => 'codigo pequeno',
            'readonly' => true,
            'disable' => true
        ));
        $formIdentificacao->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'class' => 'caixa-alta',
            'size' => 60,
            'maxlength' => 60,
            'required' => true
        ));
        $formIdentificacao->addDisplayGroup(array('id', 'descricao'), 'identificacao');
        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');


        $formRegras = new SubForm();

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Deposito\Endereco\Regra');
        $regras = array();

        foreach ($repo->findAll() as $regra) {
            $regras[$regra->getId()] = $regra->getDescricao();
        }

        $formRegras->addElement('multiCheckbox', 'regras', array(
            'multiOptions' => $regras,
            'label' => 'Regras vinculadas a esta caracteristica'
        ));

        $formRegras->addDisplayGroup(array('regras'), 'identificacao', array('legend' => 'Regras'));
        $this->addSubFormTab('Regras', $formRegras, 'regras');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\Endereco\Caracteristica 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Endereco\Caracteristica $caracteristica)
    {
        $regras = array();
        foreach ($caracteristica->getRegras()->toArray() as $regra) {
            $regras[] = $regra->getId();
        }

        $values = array(
            'identificacao' => array(
                'id' => $caracteristica->getId(),
                'descricao' => $caracteristica->getDescricao(),
            ),
            'regras' => array(
                'regras' => $regras
            )
        );
        $this->setDefaults($values);
    }

}