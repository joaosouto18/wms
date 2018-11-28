<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;
use Wms\Domain\Entity\Deposito\Endereco;

/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:25
 */

class InventarioEnderecoForm extends SubForm
{
    public function init()
    {
        $sessao = new \Zend_Session_Namespace('deposito');

        //form's attr
        $this->setAttribs(array('id' => 'criterio-inventario-form', 'class' => 'filtro'));

        //endereço
        try {
            $this->addElement('text', 'inicialRua', array(
                'size' => 3,
                'alt' => 'enderecoRua',
                'label' => 'Rua inicial',
                'ng-model' => "criterioForm.inicialRua"
            ))
            ->addElement('text', 'finalRua', array(
                'size' => 3,
                'alt' => 'enderecoRua',
                'label' => 'Rua Final',
                'ng-model' => "criterioForm.finalRua"
            ))
            ->addElement('text', 'inicialPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Prédio inicial',
                'ng-model' => "criterioForm.inicialPredio"
            ))
            ->addElement('text', 'finalPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Prédio final',
                'ng-model' => "criterioForm.finalPredio"
            ))
            ->addElement('text', 'inicialNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nível inicial',
                'ng-model' => "criterioForm.inicialNivel"
            ))
            ->addElement('text', 'finalNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nível final',
                'ng-model' => "criterioForm.finalNivel"
            ))
            ->addElement('text', 'inicialApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto inicial',
                'ng-model' => "criterioForm.inicialApartamento"
            ))
            ->addElement('text', 'finalApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto final',
                'ng-model' => "criterioForm.finalApartamento"
            ))
            ->addElement('select', 'lado', array(
                'mostrarSelecione' => false,
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => ['I' => 'Impar', 'P' => 'Par']),
                'label' => 'Lado',
                'class' => 'pequeno',
                'ng-model' => "criterioForm.lado"
            ))
            ->addElement('select', 'situacao', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => ['B' => 'Bloqueado', 'D' => 'Desbloqueado']),
                'label' => 'Situação',
                'ng-model' => "criterioForm.situacao"
            ))
            ->addElement('select', 'status', array(
                'mostrarSelecione' => false,
                'class' => 'pequeno',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => ['D' => 'Disponível', 'O' => 'Ocupado']),
                'label' => 'Status',
                'ng-model' => "criterioForm.status"
            ))
            ->addElement('select', 'ativo', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => ['S' => 'Ativo', 'N' => 'Inativo']),
                'label' => 'Ativo',
                'ng-model' => "criterioForm.ativo"
            ))
            ->addElement('select', 'idCaracteristica', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Deposito\Endereco\Caracteristica')->getIdValue()),
                'label' => 'Caractristica',
                'ng-model' => "criterioForm.caracteristica"
            ))
            ->addElement('select', 'idEstruturaArmazenagem', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Armazenagem\Estrutura\Tipo')->getIdValue()),
                'label' => 'Estrutura de Armazenagem',
                'ng-model' => "criterioForm.estrutArmaz"
            ))
            ->addElement('select', 'idTipoEndereco', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Deposito\Endereco\Tipo')->getIdValue()),
                'label' => 'Tipo de Endereço',
                'ng-model' => "criterioForm.tipoEnd"
            ))
            ->addElement('select', 'idAreaArmazenagem', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Deposito\AreaArmazenagem')->getIdValue(array('idDeposito' => $sessao->idDepositoLogado))),
                'label' => 'Área de Armazenagem',
                'ng-model' => "criterioForm.areaArmaz"
            ))
            ->addElement('button', 'btnBuscar', array(
                'class' => 'btn btn-form',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('id' => 'btn-buscar-endereco'),
                'ng-click' => "requestForm()"
            ))
            ->addElement('button', 'clearForm', array(
                'class' => 'btn btn-form',
                'label' => 'Limpar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('id' => 'btn-clear'),
                'ng-click' => "clearForm()"
            ))
            ->addDisplayGroup(array('inicialRua', 'finalRua', 'inicialPredio', 'finalPredio', 'inicialNivel', 'finalNivel', 'inicialApartamento', 'finalApartamento'), 'endereco', array('legend' => 'Intervalo de Endereços'))
            ->addDisplayGroup(array('idCaracteristica', 'idEstruturaArmazenagem', 'idTipoEndereco', 'lado', 'idAreaArmazenagem', 'situacao', 'status', 'ativo', 'btnBuscar', 'clearForm'), 'caracteristica', array('legend' => 'Características'));
        } catch (\Zend_Form_Exception $e) {
        }
    }
}