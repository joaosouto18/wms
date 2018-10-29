<?php

namespace Wms\Module\Web\Form\Atividade;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class SetorOperacional extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'atividade-setoroperacional-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'id', array(
                    'label' => 'Código Interno',
                    'class' => 'codigo',
                    'readonly' => true,
                    'disable' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus ',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addDisplayGroup(array('id', 'descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Atividade\SetorOperacional
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Atividade\SetorOperacional $setorOperacional)
    {
        $values = array(
            'identificacao' => array(
                'id' => $setorOperacional->getId(),
                'descricao' => $setorOperacional->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}