<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Regra
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Regra extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'produto-regra-form', 'class' => 'saveForm'));

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 80,
                    'maxlength' => 60,
                    'required' => true,
                ))
                ->addDisplayGroup(array('descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Produto\Regra
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Produto\Regra $regra)
    {
        $values = array(
            'descricao' => $regra->getDescricao()
        );
        $this->setDefaults($values);
    }

    /**
     *
     * @param array $data
     * @return boolean 
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        if ($this->checkAllEmpty())
            $valid = false;

        return $valid;
    }

}
