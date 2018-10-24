<?php

namespace Wms\Module\Web\Form\Armazenagem\Estrutura;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'armazenagem-estrutura-tipo-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'maxlength' => 60
                ))
                ->addDisplayGroup(array('descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\TipoEstruturaArmazenagem $tipo 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Armazenagem\Estrutura\Tipo $tipo)
    {
        $values = array(
            'identificacao' => array(
                'descricao' => $tipo->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}
