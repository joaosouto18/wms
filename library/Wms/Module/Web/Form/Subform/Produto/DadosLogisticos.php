<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto,
    Core\Form\SubForm;

/**
 * Description of DadosLogisticos
 *
 * @author medina
 */
class DadosLogisticos extends SubForm
{

    public function init()
    {
        $this->addElement('hidden', 'id')
                ->addElement('hidden', 'idNormaPaletizacao')
                ->addElement('select', 'idEmbalagem', array(
                    'mostrarSelecione' => false,
                    'label' => 'Embalagem',
                    'multiOptions' => array(),
                    'registerInArrayValidator' => false,
                ))
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
                    'label' => 'Sequência',
                    'size' => 12,
                ))
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade')
                ->addElement('hidden', 'acao')
                ->addElement('submit', 'btnDadosLogisticos', array(
                    'label' => 'Adicionar Dado Logistico',
                    'attribs' => array(
                        'id' => 'btn-salvar-dado-logistico',
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