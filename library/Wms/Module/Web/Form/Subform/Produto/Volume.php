<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto,
    Core\Form\SubForm;
use Wms\Util\Endereco;

/**
 * Description of Volume
 *
 * @author medina
 */
class Volume extends SubForm
{

    public function init()
    {
        $placeholder = Endereco::mascara();

        $this->addElement('hidden', 'id')
                ->addElement('hidden', 'idNormaPaletizacao')
                ->addElement('hidden', 'codigoBarrasAntigo')
                ->addElement('hidden', 'enderecoAntigo')
                ->addElement('text', 'altura', array(
                    'label' => 'Altura(m)',
                    'class' => 'parametro-cubagem',
                    'alt' => 'centesimal',
                    'size' => 12,
                ))
                ->addElement('text', 'largura', array(
                    'label' => 'Largura(m)',
                    'class' => 'parametro-cubagem',
                    'alt' => 'centesimal',
                    'size' => 12,
                ))
                ->addElement('text', 'profundidade', array(
                    'label' => 'Profundidade(m)',
                    'class' => 'parametro-cubagem',
                    'alt' => 'centesimal',
                    'size' => 12,
                ))
                ->addElement('text', 'cubagem', array(
                    'label' => 'Cubagem(m³)',
                    'class' => 'parametro-cubagem',
                    'alt' => 'milesimal',
                    'readonly' => true,
                    'size' => 12,
                ))
                ->addElement('text', 'peso', array(
                    'label' => 'Peso(kg)',
                    'alt' => 'centesimal',
                    'size' => 12,
                ))
                ->addElement('numeric', 'codigoSequencial', array(
                    'label' => 'Nº do Volume',
                    'size' => 12,
                ))
                ->addElement('TEXT', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 45,
                    'maxlength' => 250,
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
                    'size' => 45,
                    'maxlength' => 100,
                ))
                ->addElement('text', 'endereco', array(
                    'label' => 'Endereço',
                    'alt' => 'endereco',
                    'size' => 20,
                    'placeholder' => $placeholder,
                ))
                ->addElement('numeric', 'pontoReposicao', array(
                    'label' => 'Ponto de Reposição',
                    'size' => 10,
                    'value' => 0
                ))
                ->addElement('numeric', 'capacidadePicking', array(
                    'label' => 'Capacidade do Picking',
                    'size' => 10,
                    'value' => 0
                ))
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade')
                ->addElement('hidden', 'acao')
                ->addElement('submit', 'btnVolume', array(
                    'label' => 'Adicionar Volume',
                    'attribs' => array(
                        'id' => 'btn-salvar-volume',
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