<?php

use Wms\Module\Web\Controller\Action,
	Core\Grid;

class Web_ExemploController extends Action
{

	protected $repositotyName = '\Wms\Domain\Entity\Exemplo';

	public function indexAction()
	{
		
	}

	public function editAction()
	{
		$this->view->form = new \Wms\Module\Web\Form\Exemplo;
	}

	public function formAction()
	{
		$form = new \Wms\Module\Web\Form;

		$form->setAttrib('id', 'mainForm');
		$form->setAttrib('class', 'flora');

		$form->setDecorators(array(
			'FormElements',
			array('TabContainer', array(
					'id' => 'tabContainer',
			)),
			'Form',
		));

		$form->addSubFormTab('Pessoals', new \Wms\Module\Web\Form\Personal, 'dddddddddd');
		$form->addSubFormTab('Pessoalsss', new \Wms\Module\Web\Form\Personal, 'dddddsdfsfdddddd');

		$this->view->form = $form;

		$this->render('edit');
	}

}

