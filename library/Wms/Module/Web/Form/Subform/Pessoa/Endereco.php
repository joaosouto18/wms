<?php

namespace Wms\Module\Web\Form\Subform\Pessoa;

use \Wms\Domain\Entity\Pessoa,
    \Wms\Domain\Entity\Pessoa\Endereco\Tipo;

/**
 * Description of PessoaFisica
 *
 * @author medina
 */
class Endereco extends \Core\Form\SubForm
{

    public function init()
    {
        $addressTypes = array(
            Tipo::RECADO => 'RECADO',
            Tipo::COMERCIAL => 'COMERCIAL',
            Tipo::RESIDENCIAL => 'RESIDENCIAL',
            Tipo::ANTERIOR => 'ANTERIOR',
            Tipo::ENTREGA => 'ENTREGA',
            Tipo::COBRANCA => 'COBRANÇA',
        );

        $em = $this->getEm();
        $sigla = $em->getRepository('wms:Util\Sigla');
        $uf = $sigla->getIdValue(32);

        $this->addElement('select', 'idTipo', array(
            'label' => 'Tipo de endereço',
            'multiOptions' => $addressTypes,
            'class' => 'focus',
        ));
        $this->addElement('cep', 'cep', array(
            'label' => 'CEP',
            'class' => 'pequeno',
        ));
        $this->addElement('text', 'descricao', array(
            'label' => 'Endereço',
            'class' => 'caixa-alta',
            'maxlength' => 72,
            'size' => 50,
        ));
        $this->addElement('text', 'numero', array(
            'label' => 'Número',
            'class' => 'pequeno',
            'maxlength' => 6
        ));
        $this->addElement('text', 'complemento', array(
            'label' => 'Complemento',
            'class' => 'caixa-alta',
            'maxlength' => 36,
            'size' => 36,
        ));
        $this->addElement('text', 'bairro', array(
            'label' => 'Bairro',
            'class' => 'caixa-alta',
            'maxlength' => 72,
            'size' => 50,
        ));
        $this->addElement('text', 'localidade', array(
            'label' => 'Cidade',
            'class' => 'caixa-alta',
            'maxlength' => 72,
            'size' => 40,
        ));
        $this->addElement('select', 'idUf', array(
            'label' => 'Estado',
            'class' => 'medio',
            'multiOptions' => $uf
        ));
        $this->addElement('text', 'pontoReferencia', array(
            'label' => 'Referência',
            'class' => 'caixa-alta',
            'maxlength' => 255,
            'size' => 50,
        ));
        $this->addElement('radio', 'isEct', array(
            'label' => 'Endereço ECT',
            'multiOptions' => array('S' => 'Sim', 'N' => 'Não'),
            'value' => 'N',
            'separator' => ''
        ));
        $this->addElement('hidden', 'id');
        $this->addElement('hidden', 'acao');
        $this->addElement('hidden', 'idPessoa');

        $this->addElement('button', 'btnEndereco', array(
            'label' => 'Adicionar',
            'attribs' => array(
                'id' => 'btn-salvar-endereco',
                'class' => 'btn',
                'style' => 'display:block; clear:both;',
            ),
            'decorators' => array('ViewHelper'),
        ));
    }
    /**
     * Popula os dados de um form a partir de um objeto
     * @param Pessoa $pessoa 
     */
    public function setDefaultsFromEntity(Pessoa $pessoa)
    {
        $values = array('idPessoa' => $pessoa->getId());
        $this->setDefaults($values);
    }

}