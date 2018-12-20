<?php

namespace Wms\Module\Web\Form\Subform\Pessoa;

/**
 * Description of Juridica
 *
 * @author Renato Medina <medinadato@gmail.com>e
 */
class Juridica extends \Core\Form\SubForm
{

    public function init()
    {
	$this->setDecorators(array(
	   'FormElements',
	    array('HtmlTag', array('tag' => 'div', 'id' => 'form-pessoa-juridica-container')),
	));
		
	$em = $this->getEm();
	$sigla = $em->getRepository('wms:Util\Sigla');
	// tipo organizacao
	$tipoOrganizacao = $sigla->getIdValue(13);
	// ramo de atividade 
	$ramoAtividade = $sigla->getIdValue(12);
	
	$this->addElement('text', 'nome', array(
	    'label' => 'Razão Social',
	    'class' => 'caixa-alta focus',
            'size' => 40,
	    'maxlength' => 60,
	    'required' => true
	));
	$this->addElement('select', 'idRamoAtividade', array(
	    'label' => 'Ramo atividade',
	    'multiOptions' => $ramoAtividade,
	    'required' => true
	));
	$this->addElement('select', 'idTipoOrganizacao', array(
	    'label' => 'Tipo Organização',
	    'multiOptions' => $tipoOrganizacao,
	    'required' => true
	));
	$this->addElement('date', 'dataAbertura', array(
	    'label' => 'Data abertura',
	    'required' => true
	));
	$this->addElement('text', 'nomeFantasia', array(
	    'label' => 'Nome Fantasia',
	    'class' => 'caixa-alta ',
            'size' => 40,
	    'maxlength' => 30,
	    'required' => true
	));
	
	$this->addElement('cnpj', 'cnpj', array(
	    'label' => 'CNPJ',
	    'required' => true
	));
	
	$this->addElement('text', 'inscricaoMunicipal', array(
	    'label' => 'Inscrição municipal',
	));
	
	$this->addElement('text', 'inscricaoEstadual', array(
	    'label' => 'Inscrição estadual',
	));

	$this->addDisplayGroup(array(
	    'nome',
	    'idRamoAtividade',
	    'idTipoOrganizacao',
	    'dataAbertura',
	    'nomeFantasia',
	    'cnpj',
	    'inscricaoMunicipal',
	    'inscricaoEstadual',
	)
	    , 'identification', 
	    array('legend' => 'Identificação'
	));
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Pessoa\Juridica $pessoaJuridica)
    {
	$values = array(
	    'id' => $pessoaJuridica->getId(),
	    'nome' => $pessoaJuridica->getNome(),
	    'idRamoAtividade' => $pessoaJuridica->getIdRamoAtividade(),
	    'idTipoOrganizacao' => $pessoaJuridica->getIdTipoOrganizacao(),
	    'dataAbertura' => $pessoaJuridica->getDataAbertura(),
	    'nomeFantasia' => $pessoaJuridica->getNomeFantasia(),
	    'cnpj' => $pessoaJuridica->getCnpj(),
	    'inscricaoMunicipal' => $pessoaJuridica->getInscricaoMunicipal(),
	    'inscricaoEstadual' => $pessoaJuridica->getInscricaoEstadual(),
	);

	$this->setDefaults($values);
    }

}