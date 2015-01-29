<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Wms\Module\Web\Form\Subform\Pessoa\Juridica,
    Wms\Module\Web\Form\Subform\Pessoa\Telefone,
    Wms\Module\Web\Form\Subform\Pessoa\Endereco,
    Wms\Domain\Entity\Filial as FilialEntity;

/**
 * Description of Usuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filial extends Form
{
    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'filial-form', 'class' => 'saveForm'));
        
	$formPessoa = new Pessoa;
	$formPessoa->removeDisplayGroup('identificacao');
	$formPessoa->addElement('hidden', 'tipo', array(
	    'value' => 'J'
	));
	$formPessoa->removeSubForm('fisica');
	
	$this->prepareFormIntegracao($formPessoa);
	$this->prepareFormParametros($formPessoa);

	$this->addSubFormTab('Dados Pessoais', $formPessoa, 'pessoa');
	$this->addSubFormTab('Telefones',  new Telefone,    'telefones'  ,'forms/telefone.phtml');
	$this->addSubFormTab('Endereços',  new Endereco,    'enderecos'  ,'forms/endereco.phtml');
    }

    public function setDefaultsFromEntity(FilialEntity $filial)
    {
	$pessoa = $filial->getPessoa();
	
	$this->prepareFormIntegracao($this->getSubForm('pessoa'), $filial);
    $this->prepareFormParametros($this->getSubForm('pessoa'), $filial);

	$this->getSubForm('pessoa')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('telefones')->setDefaultsFromEntity($pessoa);
	$this->getSubForm('enderecos')->setDefaultsFromEntity($pessoa);
        
        $this->getSubForm('pessoa')
                ->getSubForm('juridica')
                ->getDisplayGroup('identification')
                ->getElement('isAtivo')
                ->setValue($filial->getIsAtivo());
    }

    private function prepareFormParametros(\Zend_Form $form, FilialEntity $filial = null) {
        $formPJ = $form->getSubForm('juridica');
        $valores_booleanos = array (
            'S'=>'SIM',
            'N'=>'NÃO'
        );

        $param1 = $formPJ->createElement('select','indRecTransbObg' , array (
            'label' => 'Obriga a realizar o recebimento do transbordo',
            'multiOptions' => $valores_booleanos,
            'class' => 'focus',
        ));

        $param2 = $formPJ->createElement('select','indLeitEtqProdTransbObg' , array (
            'label' => 'Obriga a bipar a etiqueta do produto já conferido em outra central na expedição de transbordo',
            'multiOptions' => $valores_booleanos,
            'class' => 'focus',
        ));

        $codExterno = $formPJ->createElement('text', 'codExterno', array(
            'label' => 'Cód. Externo',
            'required' => true
        ));

        $nGroup = $formPJ->addDisplayGroup(array($codExterno,$param1,$param2),'param', array('legend' => 'Parametros'));
        if ($filial != null) {
            $nGroup->getElement('codExterno')->setValue($filial->getCodExterno());
            $nGroup->getElement('indRecTransbObg')->setValue($filial->getIndRecTransbObg());
            $nGroup->getElement('indLeitEtqProdTransbObg')->setValue($filial->getIndLeitEtqProdTransbObg());
        }
        return $form;
    }

    private function prepareFormIntegracao(\Zend_Form $form, FilialEntity $filial = null)
    {
        $formPJ = $form->getSubForm('juridica');
        $idExterno = $formPJ->createElement('text', 'idExterno', array(
            'label' => 'Cód. Integração',
            'required' => true
        ));
        $ativo = $formPJ->createElement('hidden', 'isAtivo', array(
            'label' => 'Ativo',
        ));

        $group = $formPJ->getDisplayGroup('identification');
        $group->addElements(array($idExterno, $ativo));

        if ($filial != null) {
            $idExterno->setValue($filial->getIdExterno());
        }

        return $form;
    }

}