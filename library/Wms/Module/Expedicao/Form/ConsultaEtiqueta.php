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
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'codCarga', array(
                'label' => 'Cód. Carga',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'pedido', array(
                'label' => 'Cód. Pedido',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicial1', array(
                'size' => 20,
                'label' => 'Data Inicio da Expedição',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicial2', array(
                'size' => 20,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataFinal1', array(
                'label' => 'Data Final da Expedição',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataFinal2', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'situacao', array(
                'label' => 'Situação',
                'multiOptions' => $repoSigla->getIdValue(72),
                'decorators' => array('ViewHelper'),
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
                'multiOptions' => $repoFilial->getIdAndDescriptionExternoValue(),
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'centralTransbordo', array(
                'label' => 'Central Transbordo',
                'multiOptions' => $repoFilial->getIdAndDescriptionExternoValue(),
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'reimpresso', array(
                'label' => 'Reimpresso',
                'decorators' => array('ViewHelper'),
                'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => array('S' => 'Sim', 'N' => 'Não')),
            ))
            ->addElement('text', 'etiqueta', array(
                'label' => 'Etiqueta',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'codCliente', array(
                'label' => 'Cód. Cliente',
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'itinerario', array(
                'label' => 'Itinerário',
                'multiOptions' => $repoItinerario->getIdValue(),
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup($this->getElements(), 'consulta', array('legend' => 'Filtros'));
        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'etiqueta/filtro-consulta-etiqueta.phtml'))));
    }

}