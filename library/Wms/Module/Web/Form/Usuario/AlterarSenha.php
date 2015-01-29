<?php

namespace Wms\Module\Web\Form\Usuario;

use Wms\Module\Web\Form;

/**
 * Description of AlterarSenha
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class AlterarSenha extends Form {

    public function init()
    {

        $this->addAttribs(array('id' => 'usuario-alterar-senha-temp-form', 'class' => 'saveForm'))
                ->addElement('password', 'senhaAtual', array(
                    'label' => 'Senha atual',
                    'required' => true,
                    'class' => 'focus',
                ))
                ->addElement('password', 'senha', array(
                    'label' => 'Nova senha',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', false, array(5, 30)),
                    ),
                ))
                ->addElement('password', 'confirma_senha', array(
                    'label' => 'Redigite a Nova senha',
                    'required' => true,
                    'validators' => array(
                        array('identical', false, array('token' => 'senha')),
                        array('StringLength', false, array(5, 30)),
                    ),
                ))
                ->addDisplayGroup(array('senhaAtual', 'senha', 'confirma_senha'), 'identificacao', array('legend' => 'Informe a sua nova senha'));
    }

}

