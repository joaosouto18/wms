<?php

/**
 * Description of Form
 *
 * @author medina
 */

namespace Wms\Module\Web\Form\Exemplo;

class Profissional extends \Core\Form\SubForm {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'exemplo-profissional-form', 'class' => 'saveForm'));

        $this->addElement('radio', 'escolaridade', array(
            'label' => 'Escolaridade',
            'multiOptions' => array(
                '1' => 'Fundamental',
                '2' => 'Ensino Medio',
                '3' => 'Superior'
            )
            ,
            'value' => '2'
        ));

        $this->addDisplayGroup(
                array('escolaridade'), 'extras', array('legend' => 'Extras'
                ), array(
            'decorators' => array(
                'HtmlTag'
            )
                )
        );

        $this->addElement('phone', 'telefone1', array(
            'required' => true,
            'label' => 'Telefone(01)'
        ));

        $this->addElement('phone', 'telefone2', array(
            'label' => 'Telefone(02)'
        ));

        $this->addElement('phone', 'celular1', array(
            'required' => true,
            'label' => 'Celular (01)'
        ));

        $this->addElement('phone', 'celular2', array(
            'label' => 'Celular (02)'
        ));

        $this->addElement('email', 'email', array(
            'required' => true,
            'label' => 'E-mail',
            'size' => 40
        ));

        $this->addDisplayGroup(
                array('telefone1', 'telefone2', 'celular1', 'celular2', 'email'), 'contato', array('legend' => 'DescriÃƒÂ§ÃƒÂ£o')
        );

        $this->addElement('textarea', 'observacao', array(
            'label' => 'ObservaÃƒÂ§ÃƒÂµes',
            'cols' => '140',
            'rows' => '3'
        ));

        $this->addElement('submit', 'submit', array('label' => 'submit'));

        $this->addDisplayGroup(
                array('observacao', 'submit'), 'descricao', array('legend' => 'DescriÃƒÂ§ÃƒÂ£o')
        );
    }

}