<?php

namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Produto as ProdutoEntity;

class Produto extends Form {

    public function init() {
        $em = $this->getEm();

        //form's attr
        $this->setAttribs(array(
            'id' => 'produto-regra-form',
            'method' => 'get',
            'class' => 'filtro',
        ));

        $linhasSeparacao = $em->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();
        $this->addElement('text', 'id', array(
                    'label' => 'Código',
                    'size' => 10,
                    'maxlength' => 999,
                    'class' => 'focus',
                ))
                ->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 10,
                    'maxlength' => 254,
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 30,
                    'maxlength' => 40,
                ))
                ->addElement('text', 'fabricante', array(
                    'label' => 'Fabricante',
                    'size' => 30,
                    'maxlength' => 40,
                ))
                ->addElement('hidden', 'incluirinput', array(
                    'label' => 'incluir',
                    'value' => '0',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Atualizar Lista',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'incluir', array(
                    'label' => 'Incluir na Lista',
                    'class' => 'btn incluir',
                    'decorators' => array('ViewHelper'),
                ))->addElement('submit', 'limpar', array(
                    'label' => 'Limpar Lista',
                    'class' => 'btn incluir',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('multiselect', 'idLinhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'style' => 'height:auto; width:100%',
                    'multiOptions' => $linhasSeparacao,
                ))
                ->addElement('checkbox', 'picking', array(
                    'label' => 'Picking',
                    'checked' => true
                ))
                ->addElement('checkbox', 'pulmao', array(
                    'label' => 'Pulmão',
                    'checked' => true
                ))
                ->addDisplayGroup(array('picking', 'pulmao'), 'tipoEndereco', array('legend' => 'Tipo de Endereço'))
                ->addDisplayGroup(array('id', 'grade', 'descricao', 'fabricante', 'classe', 'idLinhaSeparacao', 'incluirinput', 'submit', 'incluir','limpar'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

}
