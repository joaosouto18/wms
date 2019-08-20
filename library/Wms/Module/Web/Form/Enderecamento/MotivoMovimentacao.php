<?php

namespace Wms\Module\Web\Form\Enderecamento;

use Wms\Module\Web\Form;

class MotivoMovimentacao extends Form
{
    public function init()
    {
        $this->setAttribs(array('id' => 'motivo-movimentacao-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('hidden', 'id')
            ->addElement('text', 'descricao', array(
                'label' => 'Descrição',
                'class' => 'caixa-alta focus',
                'size' => 40,
                'maxlength' => 200,
                'required' => true
            ))
            ->addDisplayGroup(array('id', 'descricao'), 'identificacao');
        $this->addSubFormTab('Cadastro de novo motivo de movimentação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Enderecamento\MotivoMovimentacao $motivo)
    {
        $this->setDefaults($motivo->toArray(true));
    }

}