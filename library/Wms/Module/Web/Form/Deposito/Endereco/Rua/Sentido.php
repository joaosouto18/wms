<?php

namespace Wms\Module\Web\Form\Deposito\Endereco\Rua;

use Wms\Module\Web\Form,
    Core\Form\SubForm;
/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Sentido extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-rua-sentido-form', 'class' => 'saveForm'));
        
	$em = $this->getEm();
	$sessao = new \Zend_Session_Namespace('deposito');
	
	$formIdentificacao = new SubForm;
	
	$formIdentificacao->addElement('hidden', 'idDeposito', array(
	    'value' => $sessao->idDepositoLogado,
	));
	
	$formIdentificacao->addElement('select', 'idRua', array(
	    'label' => 'Rua',
	    'multiOptions' => $ruasOptions,
	    'required' => true
	));
	
	$formIdentificacao->addElement('radio', 'descricao', array(
	    'label' => 'Sentido',
	    'multiOptions' => array(array(1 => 'Crescente', 2 => 'Decrescente')),
	    'class' => 'caixa-alta',
	    'required' => true
	));
	
	$formIdentificacao->addDisplayGroup(array('idDeposito', 'idEnderecoRua','descricao'), 'identificacao', array('legend' => 'Identificação'));
	$this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\Endereco\Rua\Sentido
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Endereco\Rua\Sentido $sentido)
    {
	$values = array(
	    'id' => $sentido->getId(),
	    'idEnderecoRua'=>$sentido->getIdEnderecoRua(),
	    'descricao' => $sentido->getDescricao()
	);
	$this->setDefaults($values);
    }

}

?>


