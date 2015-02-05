<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class ConsultaEtiqueta extends Form
{

    public function init()
    {
        $em = $this->getEm();

        $repoFilial = $em->getRepository('wms:Filial');
        $repoSigla = $em->getRepository('wms:Util\Sigla');
        $repoItinerario = $em->getRepository('wms:Expedicao\Itinerario');

        $this
            ->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'etiqueta', 'action' => 'consultar')))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro',
                'id' => 'filtro-consulta-etiqueta',
            ))
            ->addElement('text', 'codExpedicao', array(
                'label' => 'Cód. Expedição',
                'size' => 10,
            ))
            ->addElement('text', 'codCarga', array(
                'label' => 'Cód. Carga',
                'size' => 10,
            ))
            ->addElement('text', 'codPedido', array(
                'label' => 'Cód. Pedido',
                'size' => 10,
            ))
            ->addElement('date', 'dataInicio', array(
                'label' => 'Data Início Expedição',
                'size' => 10,
            ))
            ->addElement('date', 'dataFim', array(
                'label' => 'Data Fim Expedição',
                'size' => 10,
            ))
            ->addElement('select', 'situacao', array(
                'label' => 'Situação',
                'multiOptions' => $repoSigla->getIdValue(72),
            ))
            ->addElement('text', 'codProduto', array(
                'label' => 'Cód. Produto',
                'size' => 10,
            ))
            ->addElement('text', 'grade', array(
                'label' => 'Grade',
                'size' => 10,
            ))
            ->addElement('select', 'centralEstoque', array(
                'label' => 'Central Estoque',
                'multiOptions' => $repoFilial->getIdAndDescriptionExternoValue(),
            ))
            ->addElement('select', 'centralTransbordo', array(
                'label' => 'Central Transbordo',
                'multiOptions' => $repoFilial->getIdAndDescriptionExternoValue(),
            ))
            ->addElement('select', 'reimpresso', array(
                'label' => 'Reimpresso',
                'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => array('S' => 'Sim', 'N' => 'Não')),
            ))
            ->addElement('text', 'etiqueta', array(
                'label' => 'Etiqueta',
                'size' => 10,
            ))
            ->addElement('text', 'codCliente', array(
                'label' => 'Cód. Cliente',
                'size' => 10,
            ))
            ->addElement('select', 'itinerario', array(
                'label' => 'Itinerário',
                'multiOptions' => $repoItinerario->getIdValue(),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                'codExpedicao',
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
                'submit'),
                'consulta',
                array('legend' => 'Filtros'));
    }

}