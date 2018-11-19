<?php

namespace Wms\Module\InventarioNovo\Form;

use Wms\Module\Web\Form;
use Wms\Domain\Entity\Deposito\Endereco;

class IndexForm extends Form {

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
                ->addElement('select', 'status', array(
                    'label' => 'Status',
                    'multiOptions' => array('542'=>'GERADO',
                                                    '543' => 'LIBERADO',
                                                    '544' => 'FINALIZADO / CONCLUIDO',
                                                    '545'=>'CANCELADO'),
                ))
                ->addElement('text', 'inventario', array(
                    'label' => 'Inventário',
                    'alt' => 'number',
                    'size' => 15,
                    'class' => 'ctrSize',
                ))
                ->addElement('text', 'produto', array(
                    'label' => 'Cod. Prouto',
                    'alt' => 'number',
                    'size' => 15,
                    'class' => 'ctrSize',
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
                ->addElement('date', 'dataInicial1', array(
                    'size' => 20,
                    'label' => 'Data Inicio',
                ))
                ->addElement('date', 'dataInicial2', array(
                    'size' => 20,
                ))
                ->addElement('date', 'dataFinal1', array(
                    'size' => 20,
                    'label' => 'Data Finalização',
                ))
                ->addElement('date', 'dataFinal2', array(
                    'size' => 20,
                ))
                ->addElement('submit', 'buscar', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper')
                ))
                ->addDisplayGroup(array('rua', 'predio', 'nivel', 'apto', 'dataInicial1', 'dataInicial2', 'dataFinal1', 'dataFinal2',  'status', 'ruaFinal', 'predioFinal', 'nivelFinal', 'aptoFinal',
                    'produto', 'inventario', 'buscar'), 'identificacao', array('legend' => 'Filtros'));
    }

}
