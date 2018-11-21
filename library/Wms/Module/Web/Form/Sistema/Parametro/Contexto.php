<?php

namespace Wms\Module\Web\Form\Sistema\Parametro;

/**
 * Description of Contexto
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Contexto extends \Wms\Module\Web\Form {

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-parametro-contexto-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'id', array(
                    'label' => 'Código',
                    'class' => 'codigo pequeno focus',
                    'readonly' => true,
                    'disable' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Nome do Contexto',
                    'class' => 'caixa-alta grande',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addDisplayGroup(array('id', 'descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Sistema\Parametro\Contexto $context)
    {
        $values = array(
            'id' => $context->getId(),
            'descricao' => $context->getDescricao(),
        );

        $this->setDefaults($values);
    }

}