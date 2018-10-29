<?php

namespace Wms\Module\Web\Form\Subform;

/**
 * Description of PessoaFisica
 *
 * @author medina
 */
class Pessoa extends \Core\Form\SubForm
{

    public function init()
    {
	$this->addElement('text', 'nomPessoa', array(
	    'label' => 'Nome',
	    'class' => 'caixa-alta ',
	    'maxlength' => 60,
	    'required' => true
	));
	$this->addElement('radio', 'codTipoPessoa', array(
	    'label' => 'Tipo Pessoa',
	    'multiOptions' => array('F' => 'Física', 'J' => 'Jurídica'),
	    'required' => true
	));

	$this->addDisplayGroup(array(
	    'nomPessoa',
	    'codTipoPessoa',
	    'dscApelido',
		), 'identificacao', array('legend' => 'Identificação'
	));
    }

    public function setDefaultsFromEntity($entity)
    {
	$values = array(
	    'id' => $entity->getId(),
	    'datNascimento' => $entity->getDatNascimento(),
	    'codGrauEscolaridade' => $entity->getCodGrauEscolaridade(),
	    'dscApelido' => $entity->getDscApelido()
	);

	$this->setDefaults($values);
    }

}