<?php
namespace Wms\Module\Web\Form\Subform;

/**
 * Description of PessoaFisica
 *
 * @author medina
 */
class PessoaObservacao extends \Core\Form\SubForm
{

    public function init()
    {
	$em = \Zend_Registry::get('doctrine')->getEntityManager();
	$repo = $em->getRepository('wms:PessoaFisica');

	$this->addElement('date', 'datNascimento', array(
	    'label' => 'Data de Nascimento',
	    'required' => true
	));
	$this->addElement('text', 'codGrauEscolaridade', array(
	    'label' => 'Cod. Grau de Escolaridade',
	    'size' => 3,
	    'required' => true
	));
	$this->addElement('text', 'dscApelido', array(
	    'label' => 'Apelido',
	    'required' => true
	));

	$this->addDisplayGroup(array(
	    'datNascimento',
	    'codGrauEscolaridade',
	    'dscApelido',
		), 'identificacao', array('legend' => 'IdentificaÃƒÂ§ÃƒÂ£o'
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