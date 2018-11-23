<?php
namespace Wms\Module\InventarioNovo\Form;
use Wms\Module\Web\Form;

/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:25
 */

class InventarioProdutoForm extends Form
{
    public function init($utilizaGrade = 'S')
    {
        $this->setAttribs(array('id' => 'tab-endereco-inventario-form', 'class' => 'filtro'));
        try{
            $this->addElement('text', 'codProduto', array(
                'label' => 'Código',
                'size' => 10,
                'class' => 'focus',
                'ng-model' => 'formProduto.codProduto'
            ))
                ->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 10,
                    'ng-model' => 'formProduto.grade'
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 30,
                    'ng-model' => 'formProduto.descricao'
                ))
                ->addElement('select', 'fabricante', array(
                    'mostrarSelecione' => true,
                    'class' => 'medio',
                    'label' => 'Fabricante',
                    'multiOptions' => $this->getEm()->getRepository("wms:Fabricante")->getIdValue(),
                    'ng-model' => 'formProduto.fabricante'
                ))
                ->addElement('button', 'submit', array(
                    'label' => 'Atualizar Lista',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('button', 'incluir', array(
                    'label' => 'Incluir na Lista',
                    'class' => 'btn incluir',
                    'decorators' => array('ViewHelper'),
                ))->addElement('button', 'limpar', array(
                    'label' => 'Limpar Lista',
                    'class' => 'btn incluir',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('multiselect', 'idLinhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'style' => 'height:auto; width:100%',
                    'multiOptions' => $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue(),
                ))
                ->addElement('checkbox', 'picking', array(
                    'label' => 'Picking',
                    'checked' => true,
                    'ng-model' => 'formProduto.picking'
                ))
                ->addElement('checkbox', 'pulmao', array(
                    'label' => 'Pulmão',
                    'checked' => true,
                    'ng-model' => 'formProduto.pulmao'
                ))
                ->addDisplayGroup(array('picking', 'pulmao'), 'tipoEndereco', array('legend' => 'Tipo de Endereço'))
                ->addDisplayGroup(array('codProduto', 'grade', 'descricao', 'fabricante', 'classe', 'idLinhaSeparacao', 'incluirinput', 'submit', 'incluir','limpar'), 'identificacao', array('legend' => 'Filtros de Busca'));
        }
        catch (\Zend_Form_Exception $e) {

        }
    }
}