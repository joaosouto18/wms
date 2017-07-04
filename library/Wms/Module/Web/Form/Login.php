<?php
namespace Wms\Module\Web\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class Login extends \Core\Form
{

    public function init()
    {
	$this->setAction($this->getView()->url(array('controller' => 'auth', 'action' => 'login')));

	$this->addElement('text', 'username', array(
	    'required' => true,
	    //'validators' => array('emailAddress'),
	    'label' => 'Usuário',
	    'size' => 25,
            'class'=>'focus form-control',
	    'maxlength' => 15
	));

	$this->addElement('password', 'password', array(
	    'required' => true,
	    'label' => 'Senha',
	    'size' => 25,
            'class'=>'form-control',
	    'maxlength' => 15
	));

	$this->addElement('submit', 'submit', array(
	    'label' => 'Entrar',
	    'class' => 'btn  gradientBtn',
            'decorators' => array('ViewHelper'),
	));

	$this->addDisplayGroup(
		array('username', 'password', 'submit'), 'identification', array('legend' => 'Bem Vindo ao Wms', 'class' => 'col-xs-12')
	);
    }

}