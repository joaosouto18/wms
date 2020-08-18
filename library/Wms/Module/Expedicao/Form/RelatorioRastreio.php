<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class RelatorioRastreio extends Form
{

    public function init()
    {
        $this->setAttribs(['method' => 'get', 'class' => 'filtro', 'id' => 'form-rastreio', 'action' => 'get-expedicoes-rastreadas-ajax'])
            ->addElement('text', 'codExpedicao', array(
                'size' => 8,
                'label' => 'Expedição',
                'class' => 'focus',
                'alt' => 'number',
                'ng-model' => 'resultForm.codExpedicao',
            ))
            ->addElement('text', 'codCarga', array(
                'size' => 8,
                'ng-model' => 'resultForm.codCarga',
                'label' => 'Carga',
            ))
            ->addElement('text', 'cpfCnpj', array(
                'size' => 14,
                'label' => 'CPF/CNPJ',
                'ng-model' => 'resultForm.cpfCnpj',
                'ng-keyup' => 'formatCpfCnpj()'
            ))
            ->addElement('text', 'nomCliente', array(
                'size' => 25,
                'label' => 'Cliente',
                'ng-model' => 'resultForm.nomCliente',
                'uppercase' => true
            ))
            ->addElement('date', 'dthInicial1', array(
                'label' => 'Data Inicial',
                'ng-model' => 'resultForm.dthInicial1',
            ))
            ->addElement('date', 'dthInicial2', array(
                'label' => 'Até',
                'ng-model' => 'resultForm.dthInicial2',
            ))
            ->addElement('date', 'dthFinal1', array(
                'label' => 'Data Final',
                'ng-model' => 'resultForm.dthFinal1',
            ))
            ->addElement('date', 'dthFinal2', array(
                'label' => 'Até',
                'ng-model' => 'resultForm.dthFinal2',
            ))
            ->addElement('text', 'lote', array(
                'label' => 'Lote',
                'size' => 18,
                'ng-model' => 'resultForm.lote',
            ))
            ->addElement('button', 'Buscar', array(
                'class' => 'btn',
                'ng-click' => 'sendRequest()',
                'decorators' => array('ViewHelper'),
                'style' => 'margin-top: 20px;'
            ))
            ->addDisplayGroup(['codExpedicao', 'codCarga', 'cpfCnpj', 'nomCliente', 'dthInicial1', 'dthInicial2', 'dthFinal1', 'dthFinal2', 'lote', 'Buscar'], 'identificacao', ['legend' => 'Busca']);
    }
}