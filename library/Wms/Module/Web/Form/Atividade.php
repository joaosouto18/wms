<?php

namespace Wms\Module\Web\Form;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Atividade extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'atividade-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Atividade\SetorOperacional');

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'id', array(
                    'label' => 'Código Interno',
                    'class' => 'codigo pequeno',
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
                ->addElement('select', 'setorOperacional', array(
                    'label' => 'Setor',
                    'multiOptions' => $repo->getIdValue(),
                    'class' => 'extra',
                    'required' => true
                ))
                ->addDisplayGroup(array('id', 'descricao', 'setorOperacional'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Atividade
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Atividade $atividade)
    {
        $values = array(
            'identificacao' => array(
                'id' => $atividade->getId(),
                'descricao' => $atividade->getDescricao(),
                'setorOperacional' => $atividade->getSetorOperacional(),
            )
        );

        $this->setDefaults($values);
    }

}
