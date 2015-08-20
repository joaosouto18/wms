<?php
namespace Wms\Module\Web\Form\Recebimento;

use Wms\Module\Web\Form,
    Core\Form\SubForm;


class ModeloRecebimento extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-recebimento-form', 'class' => 'saveForm'));

        $form = new SubForm;
        $form->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'size' => 50,
            ))
            ->addElement('select', 'controleValidade', array(
                'label' => 'Controle de validade(S/N)',
                'multiOptions' => array(
                    'S' => 'SIM',
                    'N' => 'NÃO'
                ),
            ));

        $form->addDisplayGroup(array('descricao',
            'controleValidade'), 'modeloRecebimento');
        $this->addSubFormTab("Cadastro", $form, 'cadastro');
    }

}