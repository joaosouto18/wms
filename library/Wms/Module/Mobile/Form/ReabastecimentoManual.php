<?php

namespace Wms\Module\Mobile\Form;

class ReabastecimentoManual extends \Core\Form
{

    public function init()
    {
        $this
            ->addElement('text', 'codigoBarras', array(
                'required' => true,
                'label' => 'Etiqueta:',
                'size' => 40,
                'class' => 'focus form-control',
                'maxlength' => 100,
                'style' => 'width: 99%',
            ))
            ->addElement('text', 'qtd', array(
                'required' => true,
                'label' => 'Quantidade:',
                'size' => 40,
                'class' => 'form-control',
                'maxlength' => 100,
                'style' => 'width: 99%',
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn gradientBtn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('hidden', 'codOs')
            ->addDisplayGroup(
                array('codigoBarras','qtd', 'submit'), 'identification', array('legend' => 'Reabastecimento Manual')
            );

        $this->setAction($this->getView()->url(array(
            'controller' => 'enderecamento_reabastecimento-manual',
            'action' => 'index'
        )));

    }

}