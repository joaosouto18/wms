<?php

namespace Wms\Module\Web\Form\Movimentacao\Veiculo;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-veiculo-filtro-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $repoTipo = $em->getRepository('wms:Movimentacao\Veiculo\Tipo');

        $this->addElement('select', 'tipo', array(
                    'label' => 'Tipo de Veículo',
                    'mostrarSelecione' => false,
                    'class' => 'medio focus',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                ))
                ->addElement('text', 'id', array(
                    'label' => 'Placa',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'placaVeiculo'
                ))
                ->addElement('text', 'transportador', array(
                    'label' => 'Transportador',
                    'class' => 'caixa-alta',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('tipo', 'id', 'transportador', 'submit'), 'veiculo', array('legend' => 'Filtros de Busca'));
    }

    /**
     * Sets the values from entity
     * @param \Wms\Module\Web\Form\Movimentacao\Veiculo
     */
    public function setDefaultsFromEntity(\Wms\Module\Web\Form\Movimentacao\Veiculoe $veiculo)
    {
        //corpo
    }

}
