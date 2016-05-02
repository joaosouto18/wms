<?php

namespace Wms\Module\Mobile\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class ProdutoQuantidade extends \Core\Form
{

    public function init()
    {
        $normasPaletizacao = $this->getEm()->getRepository('wms:Armazenagem\Unitizador')->getIdValue();

        $this->setMethod('post')
                ->setAction($this->getView()->url(array(
                            'controller' => 'index',
                            'action' => 'produto-buscar',
                        ))
                )
                ->addElement('hidden', 'idRecebimento')
                ->addElement('hidden', 'idProduto')
                ->addElement('hidden', 'grade')
                ->addElement('select', 'idNormaPaletizacao', array(
                    'required' => true,
                    'label' => 'Unitizador',
                    'mostrarSelecione' => false,
                    'multiOptions' => $normasPaletizacao,
                ))
                ->addElement('text', 'qtdConferida', array(
                    'required' => true,
                    'label' => 'Quantidade',
                    'size' => 10,
                    'maxlength' => 15,
                    'class' => 'focus',
                ))
                ->addElement('text', 'numPeso', array(
                    'required' => true,
                    'label' => 'Peso',
                    'size' => 10,
                    'maxlength' => 15
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Salvar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ));
    }

}