<?php

namespace Wms\Module\Web\Form\Movimentacao;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity;

/**
 * Description of Veiculo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Veiculo extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-veiculo-form', 'class' => 'saveForm calcular-medidas'));

        $em = $this->getEm();
        $repoTipo = $em->getRepository('wms:Movimentacao\Veiculo\Tipo');
        $repoTransportador = $em->getRepository('wms:Pessoa\Papel\Transportador');

        //formulário
        $formVeiculo = new SubForm;

        $formVeiculo->addElement('select', 'idTipo', array(
                    'label' => 'Tipo',
                    'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => $repoTipo->getIdValue()),
                    'required' => true,
                ))
                ->addElement('select', 'idTransportador', array(
                    'label' => 'Transportador',
                    'multiOptions' => $repoTransportador->getIdValue(),
                    'required' => true,
                ))
                ->addElement('text', 'id', array(
                    'label' => 'Placa',
                    'class' => 'caixa-alta focus',
                    'alt' => 'placaVeiculo',
                    'required' => true,
                    'size' => 10,
                    'maxlength' => 7,
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta',
                    'required' => true,
                    'size' => 50,
                ))
                ->addElement('text', 'altura', array(
                    'label' => 'Altura(m)',
                    'class' => 'parametro-cubagem',
                    'id' => 'altura',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'largura', array(
                    'label' => 'Largura(m)',
                    'class' => 'parametro-cubagem',
                    'id' => 'largura',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'profundidade', array(
                    'label' => 'Profundidade(m)',
                    'class' => 'parametro-cubagem',
                    'id' => 'profundidade',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'cubagem', array(
                    'label' => 'Cubagem(m³)',
                    'id' => 'cubagem',
                    'alt' => 'milesimal',
                    'required' => true,
                    'size' => 15,
                    'readonly' => true
                ))
                ->addElement('text', 'capacidade', array(
                    'label' => 'Capacidade(kg)',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addDisplayGroup(array('idTipo', 'id', 'idTransportador', 'descricao', 'altura', 'largura', 'profundidade', 'cubagem', 'capacidade'), 'veiculo', array('legend' => 'Cadastro de Veículo'));

        $this->addSubFormTab('Identificação', $formVeiculo, 'identificacao', 'veiculo/veiculo.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Movimentacao\Veiculo $veiculo
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Movimentacao\Veiculo $veiculo)
    {
        $values = array(
            'idTipo' => $veiculo->getTipo()->getId(),
            'idTransportador' => $veiculo->getTransportador()->getId(),
            'id' => $veiculo->getId(),
            'descricao' => $veiculo->getDescricao(),
            'altura' => $veiculo->getAltura(),
            'largura' => $veiculo->getLargura(),
            'profundidade' => $veiculo->getProfundidade(),
            'cubagem' => $veiculo->getCubagem(),
            'capacidade' => $veiculo->getCapacidade(),
        );

        $this->setDefaults($values);
    }

}
