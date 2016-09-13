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
        $this->addMassAction('index/relatorio','Movimentações no Estoque (xls)');
        $this->addColumn(array(
                'label' => 'Inventário',
                'index' => 'id',
                'filter' => array(
                    'render' => array(
                        'type' => 'centesimal',
                        'range' => true,
                    ),
                ),
             ))
            ->addColumn(array(
                'label' => 'Qtd Endereços',
                'index' => 'qtdEndereco',
                'filter' => array(
                    'render' => array(
                        'type' => 'centesimal',
                        'range' => true,
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'Qtd Divergência',
                'index' => 'qtdDivergencia',
                'filter' => array(
                    'render' => array(
                        'type' => 'centesimal',
                        'range' => true,
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'Qtd Inventariado',
                'index' => 'qtdInvetariado',
                'filter' => array(
                    'render' => array(
                        'type' => 'centesimal',
                        'range' => true,
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'Dt. Início',
                'index' => 'dataInicio',
                'render' => 'DataTime',
                'filter' => array(
                    'render' => array(
                        'type' => 'date',
                        'range' => false,
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'Dt. Finalização',
                'index' => 'dataFinalizacao',
                'render' => 'DataTime',
                'filter' => array(
                    'render' => array(
                        'type' => 'date',
                        'range' => false,
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'Andamento (%)',
                'index' => 'andamento',
                'render' => 'N2',
                'filter' => array(
                    'render' => array(
                        'type' => 'centesimal',
                        'range' => true,
                    ),
                ),
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
                                                    'CONCLUIDO' => 'CONCLUIDO',
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
                    return $row['status'] == "CONCLUIDO" && $row['qtdInvetariado'] > 0 && $row['qtdDivergencia'] == 0;
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
                'label' => 'Movimentações no Estoque',
                'title' => 'Movimentações por Produto',
                'actionName' => 'view-movimentacoes-ajax',
                'cssClass' => 'pdf',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "FINALIZADO";
                },
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
            /*->addAction(array(
                'label' => 'Digitação Inventário Manual',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'digitacao-inventario-ajax',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "LIBERADO";
                },
            ))*/
            ->setHasOrdering(true);

        return $this;
    }

}
