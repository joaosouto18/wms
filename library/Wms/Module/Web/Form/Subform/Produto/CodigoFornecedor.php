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
                    'label' => 'CodigoFornecedor',
                    'size' => 80,
                ))
                ->addElement('text', 'codigo', array(
                    'label' => 'Código do produto no fornecedor',
                    'size' => 45,
                    'maxlength' => 250,
                ))
                ->addElement('submit', 'btnAdicionar', array(
                    'label' => 'Adicionar',
                    'attribs' => array(
                        'id' => 'btn-salvar-codigo-fornecedor',
                        'class' => 'btn',
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