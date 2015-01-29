<?php
namespace Wms\Module\Web\Form\Subform\Pessoa;
use \Wms\Domain\Entity\Pessoa,
    \Wms\Domain\Entity\Pessoa\Telefone\Tipo;;

/**
 * Description of PessoaFisica
 *
 * @author medina
 */
class Telefone extends \Core\Form\SubForm
{

    public function init()
    {
	$tipoTelefone = array(
	    Tipo::COBRANÇA => 'COBRANÇA',
	    Tipo::PRINCIPAL => 'PRINCIPAL',
	    Tipo::FAX => 'FAX',
	    Tipo::ENTREGA => 'ENTREGA',
	    Tipo::RECADO => 'RECADO',
	    Tipo::CELULAR => 'CELULAR',
	    Tipo::RESIDENCIAL => 'RESIDÊNCIAL',
	    Tipo::COMERCIAL => 'COMERCIAL',
	);
	
	$this->addElement('select', 'idTipo', array(
	    'label' => 'Tipo de Telefone',
	    'multiOptions' => $tipoTelefone,
            'class' => 'focus',
	));
	$this->addElement('text', 'ddd', array(
	    'label' => 'DDD',
	    'style' => 'width: 20px !important',
	    'alt' => 'ddd',
	    'maxlength' => 3,
	));
	$this->addElement('text', 'numero', array(
	    'label' => 'Número',
	    'alt'   => 'phoneNumber',
            'style' => 'width: 60px !important',
	));
	$this->addElement('text', 'ramal', array(
	    'label' => 'Ramal',
	    'alt' => 'ramal',
	    'maxlength' => 8 ,
            'style' => 'width: 40px !important',
	));
	
	$this->addElement('hidden', 'id');
	$this->addElement('hidden', 'acao');
	$this->addElement('hidden', 'idPessoa');
	
	$this->addElement('button', 'btnTelefone', array(
            'decorators' => array('ViewHelper'),
	    'label' => 'Adicionar',
	    'attribs' => array('id' => 'btn-salvar-telefone')
	));
    }
    
     /**
     * Popula os dados de um form a partir de um objeto
     * @param Pessoa $pessoa 
     */
    public function setDefaultsFromEntity(Pessoa $pessoa)
    {
	//\Zend_Debug::dump($pessoa); exit;
	$values = array('idPessoa' => $pessoa->getId());
	$this->setDefaults($values);
    }

}