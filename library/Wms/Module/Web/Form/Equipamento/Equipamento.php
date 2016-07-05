<?php

namespace Wms\Module\Web\Form\Equipamento;

use Wms\Module\Web\Form;

class Equipamento extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'equipamento-form', 'class' => 'saveForm'));

        $em = $this->getEm();

        $this->addElement('hidden', 'idEquipamento', array(
                    'label' => 'Número Equipamento',
                    'size' => 20,
                ))
            ->addElement('text', 'descricao', array(
                'label' => 'Descrição',
                'size' => 20,
            ))
            ->addElement('text','modelo', array(
                'label' => 'Modelo',
                'size'=> 20
            ))
            ->addElement('text', 'marca', array(
                'label' => 'Marca',
                'size' => 20
            ))
            ->addElement('text', 'patrimonio', array(
                'label' => 'patrimonio',
                'size' => 20,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(
                    array('idEquipamento', 'descricao', 'modelo', 'marca', 'patrimonio', 'submit'), 'Filtros', array('legend' => 'Filtros')
        );
    }
}