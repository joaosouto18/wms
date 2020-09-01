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
        $caractOptions = $this->getEm()->getRepository('wms:Deposito\Endereco\Caracteristica')->getIdValue();
        foreach ($caractOptions as $key => $value) {
            $caractOptions[$key] = [
                'label' => $value,
                'attribs' => ['ng-model' => "criterioForm.idCarac[$key]"]
            ];
        }

        $this->setAttribs(array('id' => 'criterio-inventario-form', 'class' => 'filtro'));
        try{
            $this->addElement('text', 'codProduto', array(
                    'label' => 'Código',
                    'size' => 10,
                    'ng-model' => 'criterioForm.codProduto'
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
                ->addElement('checkbox', 'incluirPicking', array(
                    'label' => 'Incluir picking (mesmo se estiver vazio)',
                    'checkedValue' => 'true',
                    'ng-model' => 'criterioForm.incluirPicking'
                ))
                ->addElement('multiCheckbox', 'idCarac', array(
                    'class' => 'medio',
                    'multiOptions' => $caractOptions,
                    'checkedValue' => true,
                    'label' => 'Caractristica',
                ))
                ->addElement('select', 'linhaSep', array(
                    'mostrarSelecione' => true,
                    'label' => 'Linha de Separação',
                    'ng-model' => 'criterioForm.linhaSep',
                    'multiOptions' => $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue(),
                ))
                ->addElement('select', 'classe', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Produto\Classe')->getIdValue()),
                    'label' => 'Classe',
                    'ng-model' => "criterioForm.classe"
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
                ));

            if ($utilizaGrade == 'S') {
                $this->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 10,
                    'ng-model' => 'criterioForm.grade'
                ));
            }

            $this->addDisplayGroup(array('criterio', 'codProduto', 'grade', 'descricao', 'fabricante', 'classe', 'linhaSep', 'idCarac', 'incluirPicking', 'btnBuscar', 'clearForm'), 'identificacao', array('legend' => 'Filtros de Busca'));
        }
        catch (\Zend_Form_Exception $e) {

        }
    }
}