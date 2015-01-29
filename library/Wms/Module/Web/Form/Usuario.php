<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Wms\Module\Web\Form\Pessoa,
    Wms\Module\Web\Form\Subform\Acesso,
    Wms\Module\Web\Form\Subform\Pessoa\Telefone,
    Wms\Module\Web\Form\Subform\Pessoa\Endereco,
    Wms\Domain\Entity\Usuario as UsuarioEntity;

/**
 * Description of Usuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Usuario extends Form
{
    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'usuario-form', 'class' => 'saveForm'));
        
	$formPessoa = new Pessoa;
	$formPessoa->removeDisplayGroup('identificacao');
	$formPessoa->addElement('hidden', 'tipo', array(
	    'value' => 'F'
	));
	$formPessoa->removeSubForm('juridica');
		
	$this->addSubFormTab('Dados Pessoais', $formPessoa, 'pessoa');
	$this->addSubFormTab('Dados de Acesso', new Acesso, 'acesso' , 'usuario/acesso.phtml');
	$this->addSubFormTab('Telefones', new Telefone, 'telefones', 'forms/telefone.phtml');
	$this->addSubFormTab('Endereços', new Endereco, 'enderecos', 'forms/endereco.phtml');
    }

    public function setDefaultsFromEntity(UsuarioEntity $usuario)
    {
	$pessoa = $usuario->getPessoa();
	
	$this->getSubForm('acesso')->setDefaultsFromEntity($usuario);
	$this->getSubForm('pessoa')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('telefones')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('enderecos')->setDefaultsFromEntity($pessoa);
    }

}