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

        $caractOptions = $this->getEm()->getRepository('wms:Deposito\Endereco\Caracteristica')->getIdValue();
        foreach ($caractOptions as $key => $value) {
            $caractOptions[$key] = [
                'label' => $value,
                'attribs' => ['ng-model' => "criterioForm.idCarac[$key]"]
            ];
        }

        //form's attr
        $this->setAttribs(array('id' => 'criterio-inventario-form', 'class' => 'filtro'));

        //endereço
        try {
            $this->addElement('text', 'ruaInicial', array(
                    'size' => 3,
                    'alt' => 'enderecoRua',
                    'label' => 'Rua inicial',
                    'ng-model' => "criterioForm.ruaInicial"
                ))
                ->addElement('text', 'ruaFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoRua',
                    'label' => 'Rua Final',
                    'ng-model' => "criterioForm.ruaFinal"
                ))
                ->addElement('text', 'predioInicial', array(
                    'size' => 3,
                    'alt' => 'enderecoPredio',
                    'label' => 'Prédio inicial',
                    'ng-model' => "criterioForm.predioInicial"
                ))
                ->addElement('text', 'predioFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoPredio',
                    'label' => 'Prédio final',
                    'ng-model' => "criterioForm.predioFinal"
                ))
                ->addElement('text', 'nivelInicial', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'label' => 'Nível inicial',
                    'ng-model' => "criterioForm.nivelInicial"
                ))
                ->addElement('text', 'nivelFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'label' => 'Nível final',
                    'ng-model' => "criterioForm.nivelFinal"
                ))
                ->addElement('text', 'aptoInicial', array(
                    'size' => 3,
                    'alt' => 'enderecoApartamento',
                    'label' => 'Apto inicial',
                    'ng-model' => "criterioForm.aptoInicial"
                ))
                ->addElement('text', 'aptoFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoApartamento',
                    'label' => 'Apto final',
                    'ng-model' => "criterioForm.aptoFinal"
                ))
                ->addElement('select', 'lado', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => ['I' => 'Impar', 'P' => 'Par']),
                    'label' => 'Lado',
                    'class' => 'pequeno',
                    'ng-model' => "criterioForm.lado"
                ))
                ->addElement('select', 'bloqueada', array(
                    'multiOptions' => [
                        'firstOpt' => 'Todos',
                        'options' => [
                            'E' => 'Apenas Entrada',
                            'S' => 'Apenas Saída',
                            'ES' => 'Ambas bloqueadas',
                            'N' => 'Ambas Liberadas'
                        ]
                    ],
                    'label' => 'Mov. Bloqueada',
                    'ng-model' => "criterioForm.bloqueada"
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
                ->addElement('multiCheckbox', 'idCarac', array(
                    'class' => 'medio',
                    'multiOptions' => $caractOptions,
                    'checkedValue' => true,
                    'label' => 'Caractristica',
                ))
                ->addElement('select', 'estrutArmaz', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Armazenagem\Estrutura\Tipo')->getIdValue()),
                    'label' => 'Estrutura de Armazenagem',
                    'ng-model' => "criterioForm.estrutArmaz"
                ))
                ->addElement('select', 'tipoEnd', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $this->getEm()->getRepository('wms:Deposito\Endereco\Tipo')->getIdValue()),
                    'label' => 'Tipo de Endereço',
                    'ng-model' => "criterioForm.tipoEnd"
                ))
                ->addElement('select', 'areaArmaz', array(
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
                ->addDisplayGroup(array('criterio', 'ruaInicial', 'ruaFinal', 'predioInicial', 'predioFinal', 'nivelInicial', 'nivelFinal', 'aptoInicial', 'aptoFinal'), 'endereco', array('legend' => 'Intervalo de Endereços'))
                ->addDisplayGroup(array('idCarac', 'estrutArmaz', 'tipoEnd', 'lado', 'areaArmaz', 'bloqueada', 'status', 'ativo', 'btnBuscar', 'clearForm'), 'caracteristica', array('legend' => 'Características'));
        } catch (\Zend_Form_Exception $e) {
        }
    }
}