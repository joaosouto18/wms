<?php

namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;
use Wms\Domain\Entity\Deposito\Endereco;
class RessuprimentoPreventivo extends Form {

    public function init() {
        $repoLinhaSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');
        $repoTipoEndereco = $this->getEm()->getRepository('wms:Deposito\Endereco\Tipo');
        $this->setAttribs(array(
            'method' => 'post',
            'class' => 'filtro',
            'id' => 'cadastro-movimentacao',
        ));
        $this->addElement('text', 'rua', array(
                    'size' => 3,
                    'label' => 'Rua Inicio',
                    'alt' => 'enderecoRua',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'predio', array(
                    'size' => 3,
                    'alt' => 'enderecoPredio',
                    'label' => 'Predio Inicio',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'nivel', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'label' => 'Nivel Inicio',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'apto', array(
                    'size' => 3,
                    'alt' => 'enderecoApartamento',
                    'label' => 'Apto Inicio',
                    'class' => 'ctrSize',
                ))
                ->addElement('select', 'ladoRua', array(
                    'label' => 'Lado da Rua',
                    'mostrarSelecione' => true,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => array(1 => 'Par', 2 => 'Ímpar')),
                ))
                ->addElement('select', 'tipoEndereco', array(
                    'label' => 'Tipo de End. Picking',
                    'multiOptions' => array('firstOpt' => 'Ambos', 'options' => $repoTipoEndereco->getIdValue()),
                ))
                ->addElement('select', 'linhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'mostrarSelecione' => true,
                    'multiOptions' => array('firstOpt' => 'Todas', 'options' => $repoLinhaSeparacao->getIdValue()),
                ))
                ->addElement('select', 'tiporessuprimento', array(
                    'label' => 'Tipo de Ressuprimento',
                    'mostrarSelecione' => true,
                    'multiOptions' => array('firstOpt' => 'Apenas Pulmão Completo', 'options' => array(1 => 'Completar Picking')),
                ))
                ->addElement('text', 'ocupacao', array(
                    'size' => 3,
                    'label' => 'Ocupação Máxima de Picking %',
                    'alt' => 'enderecoPredio',
                    'class' => 'ctrSize required',
                ))
                ->addElement('text', 'ruaFinal', array(
                    'size' => 3,
                    'label' => 'Rua Final',
                    'alt' => 'enderecoRua',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'predioFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoPredio',
                    'label' => 'Predio Final',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'nivelFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'label' => 'Nivel Final',
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'aptoFinal', array(
                    'size' => 3,
                    'alt' => 'enderecoApartamento',
                    'label' => 'Apto Final',
                    'class' => 'ctrSize',
                ))
                ->addElement('submit', 'completar', array(
                    'label' => 'Gerar Ressuprimento',
                    'class' => 'btn completar-picking',
                    'decorators' => array('ViewHelper')
                ))
                ->addElement('submit', 'limpar', array(
                    'label' => 'Limpar Pulmão',
                    'class' => 'btn limpar-pulmao',
                    'decorators' => array('ViewHelper')
                ))
                ->addDisplayGroup(array('rua', 'predio', 'nivel', 'apto', 'ladoRua', 'tipoEndereco', 'linhaSeparacao', 'ruaFinal', 'predioFinal', 'nivelFinal', 'aptoFinal'), 'identificacao', array('legend' => 'Filtros'))
                ->addDisplayGroup(array('ocupacao', 'tiporessuprimento', 'completar', 'limpar'), 'tranferencia', array('legend' => 'Parâmetros'));
    }

}
