<?php

namespace Wms\Module\Inventario\Form;

use Wms\Domain\Entity\Inventario;
use Wms\Module\Web\Form;

class Manual extends Form
{

    public function init()
    {
        $inventarioGerado = Inventario::STATUS_GERADO;
        $inventarioLiberado = Inventario::STATUS_LIBERADO;

        $inventarioRepo = $this->getEm()->getRepository('wms:Inventario');
        $inventarioEn = $inventarioRepo->findBy(array('status' => array($inventarioLiberado, $inventarioGerado)),array('id' => 'ASC'));

        $inventario = array();
        foreach ($inventarioEn as $value) {
            $inventario[$value->getId()] = $value->getId();
        }

        //form's attr
        $this->setAttribs(array(
            'id' => 'inventario-manual-form',
            'method' => 'post',
            'class' => 'filtro',
        ));
        $this->addElement('select', 'codInventario', array(
            'label' => 'Inventário',
            'mostrarSelecione' => true,
            'multiOptions' =>$inventario,
        ));
        $this->addElement('text', 'id', array(
            'label' => 'Produto',
            'size' => 10,
            'maxlength' => 10,
            'class' => 'focus',
        ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'size' => 10,
                'maxlength' => 10,
            ))
            ->addElement('text', 'codDepositoEndereco', array(
                'alt' => 'endereco',
                'label' => 'Endereço',
                'placeholder' => '00.000.00.00'
            ))
            ->addElement('text', 'qtd', array(
                'label' => 'Quantidade',
                'size' => 8,
                'maxlength' => 4,
            ))
            ->addElement('text', 'qtdAvaria', array(
                'label' => 'Qtd Avaria',
                'size' => 8,
                'maxlength' => 4
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Salvar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codInventario', 'id', 'grade', 'codDepositoEndereco', 'qtd', 'qtdAvaria', 'submit'), 'identificacao', array('legend' => 'Inventário Manual'));

    }

}
