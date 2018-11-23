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
        $em = $this->getEm();
        $sessao = new \Zend_Session_Namespace('deposito');

        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoEstrutura = $em->getRepository('wms:Armazenagem\Estrutura\Tipo');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $repoArea = $em->getRepository('wms:Deposito\AreaArmazenagem');

        //form's attr
        $this->setAttribs(array('id' => 'tab-endereco-inventario-form', 'class' => 'filtro'));

        //endereço
        try {
            $this->addElement('text', 'inicialRua', array(
                'size' => 3,
                'alt' => 'enderecoRua',
                'label' => 'Rua inicial',
                'ng-model' => "formEndereco.ruaIni"

            ))
            ->addElement('text', 'finalRua', array(
                'size' => 3,
                'alt' => 'enderecoRua',
                'label' => 'Rua Final',
                'ng-model' => "formEndereco.ruaFin"
            ))
            ->addElement('text', 'inicialPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Prédio inicial',
                'ng-model' => "formEndereco.predioIni"
            ))
            ->addElement('text', 'finalPredio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Prédio final',
                'ng-model' => "formEndereco.predioFin"
            ))
            ->addElement('text', 'inicialNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nível inicial',
                'ng-model' => "formEndereco.nivelIni"
            ))
            ->addElement('text', 'finalNivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nível final',
                'ng-model' => "formEndereco.nivelFin"
            ))
            ->addElement('text', 'inicialApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto inicial',
                'ng-model' => "formEndereco.aptoIni"
            ))
            ->addElement('text', 'finalApartamento', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto final',
                'ng-model' => "formEndereco.aptoFin"
            ))
            ->addElement('select', 'lado', array(
                'mostrarSelecione' => false,
                'multiOptions' => Endereco::$listaTipoLado,
                'label' => 'Lado',
                'class' => 'pequeno',
                'ng-model' => "formEndereco.lado"
            ))
            ->addElement('select', 'situacao', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => array('B' => 'Bloqueado', 'D' => 'Desbloqueado')),
                'label' => 'Situação',
                'ng-model' => "formEndereco.situacao"
            ))
            ->addElement('select', 'status', array(
                'mostrarSelecione' => false,
                'class' => 'pequeno',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => array('D' => 'Disponível', 'O' => 'Ocupado')),
                'label' => 'Status',
                'ng-model' => "formEndereco.status"
            ))
            ->addElement('select', 'ativo', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('S' => 'Ativo', 'N' => 'Inativo'),
                'label' => 'Ativo',
                'ng-model' => "formEndereco.ativo"
            ))
            ->addElement('select', 'idCaracteristica', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoCaracteristica->getIdValue()),
                'label' => 'Caractristica',
                'ng-model' => "formEndereco.caracteristica"
            ))
            ->addElement('select', 'idEstruturaArmazenagem', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoEstrutura->getIdValue()),
                'label' => 'Estrutura de Armazenagem',
                'ng-model' => "formEndereco.estrutArmaz"
            ))
            ->addElement('select', 'idTipoEndereco', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                'label' => 'Tipo de Endereço',
                'ng-model' => "formEndereco.tipoEnd"
            ))
            ->addElement('select', 'idAreaArmazenagem', array(
                'mostrarSelecione' => false,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoArea->getIdValue(array('idDeposito' => $sessao->idDepositoLogado))),
                'label' => 'Área de Armazenagem',
                'ng-model' => "formEndereco.areaArmaz"
            ))
            ->addElement('button', 'btnBuscar', array(
                'class' => 'btn btn-form',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('id' => 'btn-buscar-endereco'),
                'ng-click' => "findEnderecos()"
            ))
            ->addDisplayGroup(array('inicialRua', 'finalRua', 'inicialPredio', 'finalPredio', 'inicialNivel', 'finalNivel', 'inicialApartamento', 'finalApartamento'), 'endereco', array('legend' => 'Intervalo de Endereços'))
            ->addDisplayGroup(array('idCaracteristica', 'idEstruturaArmazenagem', 'idTipoEndereco', 'lado', 'idAreaArmazenagem', 'situacao', 'status', 'ativo', 'btnBuscar'), 'caracteristica', array('legend' => 'Características'));
        } catch (\Zend_Form_Exception $e) {
        }
    }
}