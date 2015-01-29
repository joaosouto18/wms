<?php

namespace Wms\Module\Web\Form\Deposito\Nota\Entrada;

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
        $this->setAttribs(array('id' => 'deposito-nota-entrada-form', 'class' => 'saveForm'));

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
     * @param \Wms\Entity\TipoNotaEntrada
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Nota\Entrada\Tipo $tipo)
    {
        $values = array(
            'identificacao' => array(
                'descricao' => $tipo->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}
