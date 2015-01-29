<?php

namespace Wms\Module\Web\Form;

use Core\Form\SubForm,
    Wms\Module\Web\Form\Subform\Pessoa\Fisica,
    Wms\Module\Web\Form\Subform\Pessoa\Juridica;

/**
 * Description of Usuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Pessoa extends SubForm
{
    public function init()
    {
	$this->addElement('text', 'nome', array(
	    'label' => 'Nome',
	    'class' => 'caixa-alta ',
	    'maxlength' => 60,
	));
	$this->addElement('radio', 'tipo', array(
	    'label' => 'Tipo Pessoa',
	    'multiOptions' => array('F' => 'Física', 'J' => 'Jurídica'),
	    'required' => true
	));

	$this->addDisplayGroup(array(
	    'nome',
	    'tipo',
	    ), 'identificacao', array('legend' => 'Identificação'
	));
	
	$this->addSubForm(new Fisica, 'fisica');
	$this->addSubForm(new Juridica, 'juridica');
	
	$this->setDecorators(array(
		'PrepareElements',
		array('ViewScript', array('viewScript' => 'forms/pessoa.phtml'),
	)));
    }
    
    public function setDefaultsFromEntity($pessoa)
    {
	$nomeForm = ($pessoa instanceof \Wms\Domain\Entity\Pessoa\Juridica) ? 'juridica' : 'fisica' ;
	$this->getSubForm($nomeForm)->setDefaultsFromEntity($pessoa);
    }

}