<?php

namespace Wms\Module\Web\Form\Usuario;

use Wms\Module\Web\Form;

/**
 * Description of SenhaProvisoria
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class SenhaProvisoria extends Form {

    public function init()
    {
        $this->addAttribs(array('id' => 'usuario-senha-provisoria-form', 'class' => 'saveForm'))
                ->addElement('password', 'senha', array(
                    'label' => 'Nova senha',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', false, array(5, 30)),
                    ),
                ))
                ->addElement('password', 'confirma_senha', array(
                    'label' => 'Redigite a senha',
                    'required' => true,
                    'validators' => array(
                        array('identical', false, array('token' => 'senha')),
                        array('StringLength', false, array(5, 30)),
                    ),
                ))
                ->addDisplayGroup(array('senha', 'confirma_senha'), 'identificacao', array('legend' => 'Informe a sua nova senha'));
    }

}