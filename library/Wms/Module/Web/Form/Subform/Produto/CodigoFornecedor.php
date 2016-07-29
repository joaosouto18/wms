<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto,
    Core\Form\SubForm;

/**
 * Description of Volume
 *
 * @author medina
 */
class CodigoFornecedor extends SubForm
{

    public function init()
    {
        $this->addElement('hidden', 'id')
                ->addElement('text', 'fornecedor', array(
                    'label' => 'Nome do Fornecedor',
                    'size' => 70,
                ))
                ->addElement('text', 'codigo', array(
                    'label' => 'Código do produto no fornecedor',
                    'size' => 45,
                    'maxlength' => 250,
                ))
                ->addElement('select','embalagem', array(
                    'label' => 'Embalagem padrão',
                    'multiOptions' => array(),
                ))
                ->addElement('button', 'btnAdicionar', array(
                    'label' => 'Adicionar',
                    'attribs' => array(
                        'id' => 'btn-salvar-codigo-fornecedor',
                        'class' => 'btn',
                        'style' => 'margin-top: 15px'
                    ),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade');
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