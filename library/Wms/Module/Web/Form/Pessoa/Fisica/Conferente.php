<?php

namespace Wms\Module\Web\Form\Pessoa\Fisica;

use Wms\Module\Web\Form,
    Wms\Module\Web\Form\Pessoa,
    Wms\Module\Web\Form\Subform\Pessoa\Telefone,
    Wms\Module\Web\Form\Subform\Pessoa\Endereco,
    Wms\Domain\Entity\Pessoa\Fisica\Conferente as ConferenteEntity;

/**
 * Description of Usuario
 *
 * @author Adriano Uliana
 */
class Conferente extends Form
{
    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'pessoa-form', 'class' => 'saveForm'));
        
	$formPessoa = new Pessoa;
	$formPessoa->removeDisplayGroup('identificacao');
	$formPessoa->addElement('hidden', 'tipo', array(
	    'value' => 'F'
	));
	$formPessoa->removeSubForm('juridica');
		
	$this->addSubFormTab('Dados Pessoais', $formPessoa, 'pessoa');
	$this->addSubFormTab('Telefones', new Telefone, 'telefones', 'forms/telefone.phtml');
	$this->addSubFormTab('Endereços', new Endereco, 'enderecos', 'forms/endereco.phtml');
    }

    public function setDefaultsFromEntity(ConferenteEntity $conferente)
    {
	$pessoa = $conferente->getPessoa();
	
	$this->getSubForm('pessoa')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('telefones')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('enderecos')->setDefaultsFromEntity($pessoa);
    }

}