<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 01/06/2018
 * Time: 10:02
 */

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;
class LoteVirgem extends Form {

    public function init() {
        $this->setAttribs(array(
            'method' => 'post',
            'class' => 'filtro',
            'id' => 'cadastro-movimentacao',
            'action' => 'web/lote-virgem/imprimir-ajax'
        ));
        $this->addElement('text', 'loteInicio', array(
            'size' => 8,
            'label' => 'Lote Inicio',
            'class' => 'ctrSize',
            ))
            ->addElement('text', 'loteFim', array(
                'size' => 8,
                'label' => 'Lote Fim',
                'class' => 'ctrSize',
            ))
            ->addElement('date', 'dataIncio', array(
                'label' => 'Data Criação Incial'
            ))
            ->addElement('date', 'dataFim', array(
                'label' => 'Data Criação Final'
            ))
            ->addElement('text', 'codProduto', array(
                'size' => 8,
                'label' => 'Cód. Produto'
            ))
            ->addElement('text', 'codLote', array(
                'size' => 8,
                'label' => 'Cód. Lote Interno',
                'alt' => 'number'
            ))
            ->addElement('text', 'lote', array(
                'size' => 8,
                'label' => 'Lote'
            ))
            ->addElement('checkbox', 'loteLimpo', array(
                'label' => 'Apenas lotes vazios'
            ))
            ->addElement('text', 'qtdLote', array(
                'size' => 3,
                'label' => 'Quantidade de Lotes',
                'alt' => 'number',
                'class' => 'ctrSize',
            ))
            ->addElement('select', 'tipoLote', array(
                'label' => 'Tipo de Lote',
                'mostrarSelecione' => true,
                'multiOptions' => array('firstOpt' => 'TODOS', 'options' => array('I' => 'LOTE INTERNO','E' => 'LOTE EXTERNO')),
            ))
            ->addElement('submit', 'gerar', array(
                'label' => 'Gerar Lotes',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))
            ->addElement('submit', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn buscar',
                'decorators' => array('ViewHelper')
            ))
            ->addDisplayGroup(array('codProduto','lote','tipoLote', 'loteLimpo', 'loteInicio', 'loteFim', 'codLote', 'dataIncio', 'dataFim','codLote', 'buscar'), 'identificacao', array('legend' => 'Filtros'))
            ->addDisplayGroup(array('qtdLote', 'gerar'), 'tranferencia', array('legend' => 'Gerar Lotes Virgens'));
    }

}