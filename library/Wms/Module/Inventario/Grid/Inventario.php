<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class Inventario extends Grid
{

    public function init()
    {
        $this->setAttrib('title','Inventario');
        $source = $this->getEntityManager()->getRepository('wms:Inventario')->getInventarios();

        $this->setSource(new \Core\Grid\Source\ArraySource($source))
            ->setId('monitoramento-inventario');
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Inventário',
                'index' => 'id'
             ))
            ->addColumn(array(
                'label' => 'Qtd Endereços',
                'index' => 'qtdEndereco',
            ))
            ->addColumn(array(
                'label' => 'Qtd End. Divergência',
                'index' => 'qtdDivergencia',
            ))
            ->addColumn(array(
                'label' => 'Qtd Inventariado',
                'index' => 'qtdInvetariado',
            ))
            ->addColumn(array(
                'label' => 'Data Início',
                'index' => 'dataInicio',
            ))
            ->addColumn(array(
                'label' => 'Data Finalização',
                'index' => 'dataFinalizacao',
            ))
            ->addColumn(array(
                'label' => 'Andamento',
                'index' => 'andamento',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status',
                'filter' => array(
                    'render' => array(
                        'type' => 'select',
                        'attributes' => array(
                            'multiOptions' => array('GERADO'=>'GERADO',
                                                    'LIBERADO' => 'LIBERADO',
                                                    'FINALIZADO' => 'FINALIZADO',
                                                    'CANCELADO'=>'CANCELADO')
                        )
                    ),
                ),


            ))
            ->addAction(array(
                'label' => 'Liberar',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'index',
                'cssClass' => '',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "GERADO";
                },
            ))
            ->addAction(array(
                'label' => 'Cancelar',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'cancelar',
                'cssClass' => '',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO" && $row['status'] != "CANCELADO";
                },
            ))
            ->addAction(array(
                'label' => 'Atualizar Estoque',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'atualizar',
                'cssClass' => '',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "LIBERADO" && $row['qtdInvetariado'] > 0 && $row['qtdDivergencia'] == 0;
                },
            ))
            ->addAction(array(
                'label' => 'Relatório Avariados',
                'modelName' => 'inventario',
                'controllerName' => 'relatorio_avaria',
                'actionName' => 'index',
                'cssClass' => 'pdf',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] != "GERADO";
                },
            ))
            ->addAction(array(
                'label' => 'Relatório Divergências',
                'modelName' => 'inventario',
                'controllerName' => 'relatorio_divergencia',
                'actionName' => 'index',
                'cssClass' => 'pdf',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] != "GERADO";
                },
            ))
            ->addAction(array(
                'label' => 'Adicionar endereços',
                'modelName' => 'inventario',
                'controllerName' => 'parcial',
                'actionName' => 'endereco',
                'cssClass' => '',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO" && $row['status'] != "CANCELADO";
                },
            ))
            ->addAction(array(
                'label' => 'Visualizar Andamento',
                'title' => 'Andamento do Inventário',
                'actionName' => 'view-andamento-ajax',
                'cssClass' => 'view-andamento dialogAjax',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Imprimir Endereços',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'imprimir-enderecos-ajax',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "LIBERADO";
                },
            ))
            ->addAction(array(
                'label' => 'Digitação Inventário Manual',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'digitacao-inventario-ajax',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "LIBERADO";
                },
            ));

        return $this;
    }

}
