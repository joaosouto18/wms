<?php
namespace Wms\Module\Produtividade\Form;

use Wms\Module\Web\Form;

class FormProdutividade extends Form
{
    public function init()
    {
        $this->setAction(
            $this->getView()->url(array(
                'module' =>'produtividade',
                'controller' => 'relatorio_indicadores',
                'action' => 'index'
                )
            ))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro'
            ))
            ->addElement('select', 'orientacao', array(
                'label' => 'Agrupar por:',
                'value' => 'atividade',
                'multiOptions' => array(
                    'atividade' => 'Atividade',
                    'funcionario' => 'Funcionário'
                )
            ))
            ->addElement('select', 'atividade', array(
                'label' => 'Atividade:',
                'value' => 'operacao',
                'multiOptions' => array(
                    'CONF. RECEBIMENTO' => 'CONF. RECEBIMENTO',
                    'ENDERECAMENTO' => 'ENDERECAMENTO',
                    'DESCARREGAMENTO' => 'DESCARREGAMENTO',
                    'SEPARACAO' => 'SEPARACAO',
                    'CARREGAMENTO' => 'CARREGAMENTO',
                    'CONF. SEPARACAO' => 'CONF. SEPARACAO',
                )
            ))
            ->addElement('date', 'dataInicio', array(
                'label' => 'Data inicial',
                'size' => 10,
            ))
            ->addElement('date', 'dataFim', array(
                'label' => 'Data final',
                'size' => 10,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'gerarPdf', array(
                'label' => 'Gerar relatório',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))
            ->addDisplayGroup(array('atividade', 'dataInicio', 'dataFim', 'orientacao','submit', 'gerarPdf'), 'apontamento', array('legend' => 'Relatório de produtividade')
        );
    }
}