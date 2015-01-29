<?php

namespace Wms\Module\Web\Form\Deposito\Endereco;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-tipo-form', 'class' => 'saveForm calcular-medidas'));

	$formIdentificacao = new \Core\Form\SubForm();
	$formIdentificacao->addElement('text', 'descricao', array(
	    'label' => 'Descrição',
	    'class' => 'caixa-alta focus',
	    'maxlength' => 60,
	    'required' => true,
            'size' => 67,
	));

	$formIdentificacao->addElement('text', 'largura', array(
            'label' => 'Largura(m)',
	    'class' => 'parametro-cubagem',
	    'id' => 'largura',
	    'alt' => 'decimal',
	    'required' => true,
            'size' => 15,
	));

	$formIdentificacao->addElement('text', 'altura', array(
            'label' => 'Altura(m)',
	    'class' => 'parametro-cubagem',
	    'id' => 'altura',
	    'alt' => 'decimal',
	    'required' => true,
	    'size' => 15,
	));

	$formIdentificacao->addElement('text', 'profundidade', array(
            'label' => 'Profundidade(m)',
	    'class' => 'parametro-cubagem',
	    'id' => 'profundidade',
	    'alt' => 'decimal',
	    'required' => true,
	    'size' => 15,
	));

	$formIdentificacao->addElement('text', 'cubagem', array(
            'label' => 'Cubagem(m³)',
	    'id' => 'cubagem',
	    'alt' => 'decimal',
	    'required' => true,
	    'readonly' => true,
            'size' => 15,
	));

	$formIdentificacao->addElement('text', 'capacidade', array(
            'label' => 'Capacidade(kg)',
	    'id' => 'capacidade',
	    'alt' => 'centesimal',
	    'required' => true,
	    'size' => 15,
	));

	$formIdentificacao->addDisplayGroup(array('descricao', 'altura', 'largura', 'profundidade', 'cubagem', 'capacidade'), 'identificacao', array('legend' => 'Identificação'));

	$this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao', 'tipo-endereco/tipo-endereco.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\TipoEndereco $tipo 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Endereco\Tipo $tipo)
    {
	$values = array(
	    'identificacao' => array(
		'descricao' => $tipo->getDescricao(),
		'altura' => $tipo->getAltura(),
		'largura' => $tipo->getLargura(),
		'profundidade' => $tipo->getProfundidade(),
		'cubagem' => $tipo->getCubagem(),
		'capacidade' => $tipo->getCapacidade(),
	    )
	);

	$this->setDefaults($values);
    }

}
