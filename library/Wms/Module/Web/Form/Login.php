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
            'class'=>'focus col-xs-12',
	    'maxlength' => 15
	));

	$this->addElement('password', 'password', array(
	    'required' => true,
	    'label' => 'Senha',
	    'size' => 25,
            'class'=>'col-xs-12',
	    'maxlength' => 15
	));

	$this->addElement('submit', 'submit', array(
	    'label' => 'Entrar',
	    'class' => 'btn col-xs-12',
            'decorators' => array('ViewHelper'),
	));

	$this->addDisplayGroup(
		array('username', 'password', 'submit'), 'identification', array('legend' => 'Bem Vindo ao Wms', 'class' => 'col-xs-12')
	);
    }

}