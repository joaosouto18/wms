<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Tipo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'produto-tipo-form', 'class' => 'saveForm'));

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addDisplayGroup(array('id', 'descricao', 'submit'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');

        $formRegras = new SubForm();
        $em = $this->getEm();
        $repo = $em->getRepository('wms:Produto\Regra');
        $regras = array();
        foreach ($repo->findAll() as $regra) {
            $regras[$regra->getId()] = $regra->getDescricao();
        }

        $formRegras->addElement('multiCheckbox', 'regras', array(
                    'multiOptions' => $regras,
                    'label' => 'Regras vinculadas a este tipo'
                ))
                ->addDisplayGroup(array('regras'), 'identificacao', array('legend' => 'Regras'));
        $this->addSubFormTab('Regras', $formRegras, 'regras');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Produto\Tipo
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Produto\Tipo $tipo)
    {
        $regras = array();
        foreach ($tipo->getRegras()->toArray() as $regra) {
            $regras[] = $regra->getId();
        }

        $values = array(
            'identificacao' => array(
                'descricao' => $tipo->getDescricao(),
            ),
            'regras' => array(
                'regras' => $regras
            )
        );
        $this->setDefaults($values);
    }

}
