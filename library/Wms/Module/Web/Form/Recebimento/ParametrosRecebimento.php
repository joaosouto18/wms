<?php
namespace Wms\Module\Web\Form\Recebimento;

use Wms\Module\Web\Form,
    Core\Form\SubForm;


class ParametrosRecebimento extends Form
{

    public function init()
    {
        $this->setAttribs(array('id' => 'form-parametros-recebimento', 'class' => 'saveForm'));

        $form = new SubForm;
        $form->addElement('select', 'recebimento', array(
            'label' => 'Modelo de Endereçamento',
            'multiOptions' => array(
                'S' => 'SIM',
                'N' => 'NÃO'
            )))
            ->addElement('submit', 'salvar', array(
                'class' => 'btn',
                'style' => 'display:block',
                'label' => 'Salvar',
            ));
        $form->addDisplayGroup(array('recebimento', 'salvar'), 'parametroRecebimento');
        $this->addSubFormTab("Recebimento", $form, 'recebimento');
    }

}