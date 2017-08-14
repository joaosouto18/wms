<?php
namespace Wms\Module\Armazenagem\Form\MovimentacaoProduto;

use Wms\Module\Web\Form;
use Wms\Util\Endereco;

class Filtro extends Form
{

    public function init($utilizaGrade = 'S')
    {

        $this->setAttribs(array(
           'method' => 'post',
           'class' => 'filtro',
           'id' => 'relatorio-movimentacao_produto',
        ));
        $this->addElement('text', 'idProduto', array(
           'size' => 12,
           'label' => 'Cod. produto',
           'class' => 'focus',
        ));
        if ($utilizaGrade == "S") {
            $this->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ));
        } else {
            $this->addElement('hidden', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ));
        }
        $this->addElement('date', 'dataInicial', array(
            'size' => 20,
            'label' => 'Data Inicio'
        ))
        ->addElement('date', 'dataFim', array(
            'size' => 10,
            'label' => 'Data Fim'
        ))
        ->addElement('select', 'tipoMovimentacao', array(
            'label' => 'Tipo Movimentação',
            'mostrarSelecione' => true,
            'style' => 'height: auto; width: 100%',
            'multiOptions' => array('E' => 'Entrada', 'S' => 'Saída')
        ))
        ->addElement('select', 'tipoOperacao', array(
            'label' => 'Tipo Operação',
            'mostrarSelecione' => true,
            'style' => 'height: auto; width: 100%',
            'multiOptions' => array(
                'M' => 'Movimentação Manual',
                'I' => 'Inventário',
                'R' => 'Ressuprimento',
                'S' => 'Expedição',
                'E' => 'Endereçamento')
        ))
        ->addElement('select', 'tipoEndereco', array(
            'label' => 'Tipo Endereço',
            'multiOptions' => array('firstOpt' => 'Ambos', 'options' => array(
                1 => 'Picking',
                2 => 'Pulmão')),
        ))
        ->addElement('select', 'ordem', array(
            'label' => 'Ordenação',
            'multiOptions' => array('firstOpt' => 'Produto', 'options' => array(1 => 'Endereço')),
        ))
        ->addElement('text', 'rua', array(
            'size' => 3,
            'alt' => 'enderecoRua',
            'label' => 'Rua',
            'class' => 'focus',
        ))
        ->addElement('text', 'predio', array(
            'size' => 3,
            'alt' => 'enderecoPredio',
            'label' => 'Prédio',
        ))
        ->addElement('text', 'nivel', array(
            'size' => 3,
            'alt' => 'enderecoNivel',
            'label' => 'Nível',
        ))
        ->addElement('text', 'apto', array(
            'size' => 3,
            'alt' => 'enderecoApartamento',
            'label' => 'Apto',
        ))
        ->addElement('submit', 'submit', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ))
        ->addDisplayGroup(array('idProduto',  'grade', 'dataInicial', 'dataFim', 'tipoMovimentacao', 'tipoOperacao', 'rua', 'predio', 'nivel', 'apto','ordem','tipoEndereco', 'submit'), 'identificacao', array('legend' => 'Filtro'));
    }
/**
     *
     * @param array $params
     * @return boolean
     */
    public function isValid($params)
    {
        extract($params);

        if (!parent::isValid($params))
            return false;

        if ($this->checkAllEmpty())
           return false;

        return true;
    }

}