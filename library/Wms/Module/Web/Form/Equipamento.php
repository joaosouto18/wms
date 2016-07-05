<?php

namespace Wms\Module\Web\Form;

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
            ->addDisplayGroup(
                    array('idEquipamento', 'descricao', 'modelo', 'marca', 'patrimonio'), 'Cadastros', array('legend' => 'Cadastros')
        );
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Equipamento
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Equipamento $equipamento)
    {
        $values = array(
            'descricao' => $equipamento->getDescricao(),
            'modelo' => $equipamento->getModelo(),
            'marca' => $equipamento->getMarca(),
            'patrimonio' => $equipamento->getPatrimonio()
        );

        $this->setDefaults($values);
    }

}