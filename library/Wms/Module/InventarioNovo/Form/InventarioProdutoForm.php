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
    public function init($utilizaGrade = 'N')
    {
        $this->setAttribs(array('id' => 'criterio-inventario-form', 'class' => 'filtro'));
        try{
            $this->addElement('text', 'codProduto', array(
                'label' => 'Código',
                'size' => 10,
                'class' => 'focus',
                'ng-model' => 'criterioForm.codProduto'
            ))
                ->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 10,
                    'ng-model' => 'criterioForm.grade'
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 30,
                    'ng-model' => 'criterioForm.descricao'
                ))
                ->addElement('select', 'fabricante', array(
                    'mostrarSelecione' => true,
                    'class' => 'medio',
                    'label' => 'Fabricante',
                    'multiOptions' => $this->getEm()->getRepository("wms:Fabricante")->getIdValue(),
                    'ng-model' => 'criterioForm.fabricante'
                ))
                ->addElement('select', 'idLinhaSeparacao', array(
                    'mostrarSelecione' => true,
                    'label' => 'Linha de Separação',
                    'ng-model' => 'criterioForm.idLinhaSeparacao',
                    'multiOptions' => $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue(),
                ))
                ->addElement('select', 'idCaracteristica', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Deposito\Endereco\Caracteristica')->getIdValue()),
                    'label' => 'Caractristica',
                    'ng-model' => "criterioForm.caracteristica"
                ))
                ->addElement('button', 'btnBuscar', array(
                    'class' => 'btn btn-form',
                    'label' => 'Buscar',
                    'decorators' => array('ViewHelper'),
                    'attribs' => array('id' => 'btn-buscar'),
                    'ng-click' => "requestForm()"
                ))
                ->addElement('button', 'clearForm', array(
                    'class' => 'btn btn-form',
                    'label' => 'Limpar',
                    'decorators' => array('ViewHelper'),
                    'attribs' => array('id' => 'btn-clear'),
                    'ng-click' => "clearForm()"
                ))
                ->addDisplayGroup(array('codProduto', 'grade', 'descricao', 'fabricante', 'classe', 'idLinhaSeparacao', 'idCaracteristica', 'btnBuscar', 'clearForm'), 'identificacao', array('legend' => 'Filtros de Busca'));
        }
        catch (\Zend_Form_Exception $e) {

        }
    }
}