<?php

namespace Wms\Module\Web\Form\Deposito;

use Wms\Module\Web\Form,
    Core\Form\SubForm;
/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Box extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-form', 'class' => 'box saveForm'));
        
	$em = $this->getEm();
	$repoBox = $em->getRepository('wms:Deposito\Box');
	$sessao = new \Zend_Session_Namespace('deposito');
	$idDeposito = $sessao->idDepositoLogado;
	$deposito = $em->find('wms:Deposito', $idDeposito);
	$boxesPai = $repoBox->getIdValue(array('idDeposito' => $sessao->idDepositoLogado));
	$formIdentificacao = new SubForm;
	
	$formIdentificacao->addElement('hidden', 'idDeposito', array(
	    'value' => $deposito->getId(),
	));
	
	if (count($boxesPai) > 0) {
	    $formIdentificacao->addElement('select', 'idPai', array(
		'label' => 'Box Pai',
		'multiOptions' => $boxesPai,
                'class' => 'focus',
	    ));
	}

	$formIdentificacao->addElement('text', 'id', array(
	    'label' => 'Código',
	    'class' => 'pequeno',
	    'required' => true,
	    'helper' => 'formCompositeId'
	));
		
	$formIdentificacao->addElement('text', 'descricao', array(
	    'label' => 'Descrição',
	    'class' => 'caixa-alta',
            'size' => 60,
	    'maxlength' => 60,
	    'required' => true
	));
	
	$formIdentificacao->addDisplayGroup(array('idDeposito', 'idPai',  'id', 'descricao'), 'identificacao');
	
	$this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\Box
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Box $box)
    {
	$values = array(
	    'idDeposito' => $box->getIdDeposito(),
	    'idPai' => $box->getIdPai(),
	    'id' => $box->getId(),
	    'descricao' => $box->getDescricao()
	);

	$this->setDefaults($values);
    }

}