<?php

namespace Wms\Module\Web\Form\Recebimento\Divergencia;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Motivo extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'recebimento-divergencia-motivo-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'class' => 'caixa-alta focus',
            'size' => 60,
            'maxlength' => 60
        ));

        $formIdentificacao->addDisplayGroup(array('descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\MotivoDivergenciaRecebimento
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Recebimento\Divergencia\Motivo $motivo)
    {
        $values = array(
            'identificacao' => array(
                'descricao' => $motivo->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}