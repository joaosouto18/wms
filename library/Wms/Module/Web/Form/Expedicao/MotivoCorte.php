<?php

namespace Wms\Module\Web\Form\Expedicao;

/**
 * Description of SystemContextParam
 *
 */
class MotivoCorte extends \Wms\Module\Web\Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'motivo-corte-form', 'class' => 'saveForm calcular-medidas'));

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

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Expedicao/MotivoCorte
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Expedicao\MotivoCorte $motivoCorte)
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
