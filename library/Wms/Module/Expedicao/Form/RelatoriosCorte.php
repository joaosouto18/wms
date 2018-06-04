<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;
use Wms\Util\WMS_Exception;

class RelatoriosCorte extends Form
{

    public function init()
    {
        $this
            ->setAttribs(array(
                'method' => 'get',
            ));

            $this->addElement('text', 'idExpedicao', array(
                'size' => 10,
                'label' => 'Código da Expedicao',
                'class' => 'focus',
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
            ->addElement('text', 'codCargaExterno', array(
                'size' => 10,
                'label' => 'Carga',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('text', 'pedido', array(
                'size' => 10,
                'label' => 'Pedido',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codExpedicao','codCarga','dataInicial1','dataInicial2', 'submit'), 'identificacao', array('legend' => 'Filtro de Expedições'));
            $this->setDecorators(array(array('ViewScript', array('viewScript' => 'corte/relatorio-filtro.phtml'))));
    }

}