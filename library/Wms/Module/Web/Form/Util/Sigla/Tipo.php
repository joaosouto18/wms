<?php

namespace Wms\Module\Web\Form\Util\Sigla;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Util\Sigla\Tipo as TipoSiglaEntity;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'util-sigla-tipo-form', 'class' => 'saveForm'));

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 80,
                    'maxlength' => 60,
                    'required' => true,
                ))
                ->addElement('radio', 'isSistema', array(
                    'label' => 'Identificação',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'required' => true,
                    'value' => 'S',
                    'separator' => '',
                ))
                ->addDisplayGroup(array('descricao', 'isSistema'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Util\Sigla\Tipo
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Util\Sigla\Tipo $tipo)
    {
        $values = array(
            'descricao' => $tipo->getDescricao(),
            'isSistema' => $tipo->getIsSistema()
        );

        $this->setDefaults($values);
    }

}
