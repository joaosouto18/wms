<?php

namespace Wms\Module\Web\Form\Armazenagem;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class LinhaSeparacao extends \Wms\Module\Web\Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'armazenagem-linhaseparacao-form', 'class' => 'saveForm calcular-medidas'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'id', array(
                    'label' => 'Código',
                    'class' => 'codigo pequeno',
                    'readonly' => true,
                    'disable' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addDisplayGroup(array('id', 'descricao'), 'identificacao');
        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\LinhaSeparacao
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Armazenagem\LinhaSeparacao $linha)
    {
        $values = array(
            'identificacao' => array(
                'id' => $linha->getId(),
                'descricao' => $linha->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}
