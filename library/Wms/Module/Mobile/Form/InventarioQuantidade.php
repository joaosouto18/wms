<?php

namespace Wms\Module\Mobile\Form;

class InventarioQuantidade extends \Core\Form
{

    public function init()
    {
        $this->setMethod('post')
                ->setAction($this->getView()->url(array(
                            'controller' => 'inventario',
                            'action' => 'confirma-contagem',
                        ))
                )
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade')
                ->addElement('hidden', 'idEndereco')
                ->addElement('hidden', 'idContagemOs')
                ->addElement('hidden', 'codigoBarras')
                ->addElement('hidden', 'idInventarioEnd')
                ->addElement('hidden', 'codProdutoEmbalagem')
                ->addElement('hidden', 'codProdutoVolume')
                ->addElement('hidden', 'contagemEndId')
                ->addElement('hidden', 'numContagem');

                $text = new \Zend_Form_Element_Text('descricaoProduto');
                $dscEmbalagem = new \Zend_Form_Element_Text('dscEmbalagem');

                $text->helper = 'formNote';
                $dscEmbalagem->helper = 'formNote';

                $this->addElement($text)
                     ->addElement($dscEmbalagem)

                ->addElement('text', 'qtdConferida', array(
                    'required' => true,
                    'label' => 'Quantidade',
                    'size' => 10,
                    'maxlength' => 15,
                    'class' => 'focus',
                ))
                ->addElement('text', 'validade', array(
                    'label' => 'Validade',
                    'size' => 10,
                    'maxlength' => 8,
                    'placeholder' => 'dd/mm/yy'
                ))
                ->addElement('text', 'qtdAvaria', array(
                    'required' => true,
                    'label' => 'Qtd Avaria',
                    'size' => 10,
                    'value' => 0,
                    'maxlength' => 15
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Salvar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ));
    }

}