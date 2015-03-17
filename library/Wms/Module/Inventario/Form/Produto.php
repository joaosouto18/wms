<?php

namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Produto as ProdutoEntity;

class Produto extends Form
{

    public function init()
    {
        $em = $this->getEm();

        //form's attr
        $this->setAttribs(array(
            'id' => 'produto-regra-form',
            'method' => 'get',
            'class' => 'filtro',
        ));

        $linhasSeparacao = $em->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();
        $this->addElement('numeric', 'id', array(
            'label' => 'Código',
            'size' => 10,
            'maxlength' => 10,
            'class' => 'focus',
        ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'size' => 10,
                'maxlength' => 10,
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
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'idLinhaSeparacao', array(
                'label' => 'Linha de Separação',
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
            ->addDisplayGroup(array('picking','pulmao'),'tipoEndereco',array('legend'=>'Tipo de Endereço'))
            ->addDisplayGroup(array('id', 'grade', 'descricao', 'fabricante', 'classe', 'idLinhaSeparacao', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));

    }

}
