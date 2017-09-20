<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto,
    Core\Form\SubForm,
    Wms\Util\Endereco;

/**
 * Description of Embalagem
 *
 * @author medina
 */
class Embalagem extends SubForm
{

    public function init()
    {
        $placeholder = Endereco::mascara();

        $this->addElement('hidden', 'id')
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade')
                ->addElement('hidden', 'codigoBarrasAntigo')
                ->addElement('hidden', 'enderecoAntigo')
                ->addElement('hidden', 'dataInativacao')
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 45,
                    'required' => true,
                    'maxlength' => 60,
                ))
                ->addElement('text', 'quantidade', array(
                    'label' => 'Quantidade de itens',
                    'required' => true,
                    'size' => 10
                ))
                ->addElement('select', 'isPadrao', array(
                    'mostrarSelecione' => false,
                    'label' => 'Padrão Recebimento',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'value' => 'S',
                ))
                ->addElement('select', 'CBInterno', array(
                    'mostrarSelecione' => false,
                    'label' => 'Cod. Barras Automático',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'value' => 'N',
                ))
                ->addElement('select', 'imprimirCB', array(
                    'mostrarSelecione' => false,
                    'label' => 'Imprimir Etiqueta de Cod. Barras',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'value' => 'N',
                ))
                ->addElement('text', 'codigoBarras', array(
                    'label' => 'Código de Barras',
                    'required' => true,
                    'size' => 40,
                    'maxlength' => 60,
                ))
                ->addElement('text', 'endereco', array(
                    'label' => 'Endereço',
                    'alt' => 'endereco',
                    'size' => 20,
                    'placeholder' => $placeholder,
                ))
                ->addElement('select', 'embalado', array(
                    'mostrarSelecione' => false,
                    'label' => 'Embalado',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'value' => 'N',
                ))
               ->addElement('text', 'pontoReposicao', array(
                   'label' => 'Ponto de Reposição',
                   'size' => 10,
                   'value' => 0,
                   'alt' => 'centesimal'
                ))
                ->addElement('text', 'capacidadePicking', array(
                    'label' => 'Capacidade do Picking',
                    'size' => 10,
                    'value' => 0,
                    'alt' => 'centesimal'
                ))
                ->addElement('hidden', 'acao', array(
                    'value' => 'incluir',
                ))
                ->addElement('submit', 'btnEmbalagem', array(
                    'label' => 'Adicionar',
                    'attribs' => array(
                        'id' => 'btn-salvar-embalagem',
                        'class' => 'btn',
                        'style' => 'display:block; clear:both;',
                    ),
                    'decorators' => array('ViewHelper'),
                ));
    }

    /**
     * Popula os dados de um form a partir de um objeto
     * @param Produto $produto
     */
    public function setDefaultsFromEntity(Produto $produto)
    {
        $values = array(
            'idProduto' => $produto->getId(),
            'grade' => $produto->getGrade(),
        );
        $this->setDefaults($values);
    }

}