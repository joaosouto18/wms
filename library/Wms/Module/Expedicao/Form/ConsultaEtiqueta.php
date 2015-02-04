<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class ConsultaEtiqueta extends Form
{

    public function init()
    {
        $this
            ->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'relatorio_dados-movimentacao', 'action' => 'index')))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro',
                'id' => 'filtro-consulta-etiqueta',
            ))
            ->addElement('text', 'codExpedicao', array(
                'label' => 'oi',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'codCarga', array(
                'label' => 'Cód. Carga',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'codPedido', array(
                'label' => 'Cód. Pedido',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicio', array(
                'label' => 'Data Expedição',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataFim', array(
                'label' => 'Data Expedição',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'situacao', array(
                'label' => 'Situação',
                'multiOptions' => array('Oi', 'Opa'),
            ))
            ->addElement('text', 'codProduto', array(
                'label' => 'Cód. Produto',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'centralEstoque', array(
                'label' => 'Central Estoque',
                'multiOptions' => array('Oi', 'Opa'),
            ))
            ->addElement('select', 'centralTransbordo', array(
                'multiOptions' => array('Oi', 'Opa'),
            ))
            ->addElement('select', 'reimpresso', array(
                'multiOptions' => array('Oi', 'Opa'),
            ))
            ->addElement('text', 'etiqueta', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'codCliente', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'itinerario', array(
                'multiOptions' => array('Oi', 'Opa'),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                    'codCarga',
                    'codPedido',
                    'dataInicio',
                    'dataFim',
                    'situacao',
                    'codProduto',
                    'grade',
                    'centralEstoque',
                    'centralTransbordo',
                    'reimpresso',
                    'etiqueta',
                    'codCliente',
                    'itinerario',
                    'codExpedicao',
                    'submit'),
                'consulta',
                array('legend' => 'Filtros'));
    }

}