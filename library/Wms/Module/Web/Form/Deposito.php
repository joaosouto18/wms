<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Deposito
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Deposito extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Filial');

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'id', array(
                    'label' => 'Código Interno',
                    'class' => 'codigo',
                    'readonly' => true,
                    'disable' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'maxlength' => 40,
                    'required' => true
                ))
                ->addElement('select', 'idFilial', array(
                    'label' => 'Filial',
                    'multiOptions' => $repo->getIdValue(),
                    'required' => true
                ))
                ->addElement('hidden', 'isAtivo', array(
                    'label' => 'Ativo',
                    'class' => 'depositoAtivo',
                ))
                ->addDisplayGroup(array('id', 'descricao', 'idFilial', 'isAtivo'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito $deposito)
    {
        $values = array(
            'id' => $deposito->getId(),
            'idFilial' => $deposito->getIdFilial(),
            'descricao' => $deposito->getDescricao(),
            'isAtivo' => $deposito->getIsAtivo()
        );

        $this->setDefaults($values);
    }

}
