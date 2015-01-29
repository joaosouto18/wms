<?php

namespace Wms\Module\Web\Form\Deposito\Endereco;

use Wms\Module\Web\Form,
    Core\Form\SubForm;
/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Regra extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-regra-form', 'class' => 'saveForm'));
        
	$formIdentificacao = new SubForm;
	$formIdentificacao->addElement('text', 'id', array(
	    'label' => 'Código Interno',
	    'class' => 'codigo pequeno',
	    'decorators' => array('ViewHelper'),
	    'readonly' => true,
	    'disable' => true
	));
	$formIdentificacao->addElement('text', 'descricao', array(
	    'label' => 'Descrição',
	    'class' => 'caixa-alta grande',
	    'decorators' => array('ViewHelper'),
	    'maxlength' => 60,
	    'required' => true
	)); 
	
	$formIdentificacao->addDisplayGroup(array('id' ,'descricao'), 'identificacao', array('legend' => 'Identificação'));
	$this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao', 'forms/deposito-endereco-regra-form.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\Endereco\Regra
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Endereco\Regra $regra)
    {
	$values = array(
	    'id' => $regra->getId(),
	    'descricao' => $regra->getDescricao()
	);
	$this->setDefaults($values);
    }

}


