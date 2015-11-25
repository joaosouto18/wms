<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class Inventario extends Grid
{

    public function init()
    {
        $this->setAttrib('title','Inventario');
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("i, s.sigla as status, count(ie.id) as qtdEndereco")
            ->addSelect("
                    (
                        SELECT COUNT(ie2.id)
                        FROM wms:Inventario\Endereco ie2
                        WHERE ie2.divergencia = 1
                        AND ie2.inventario = i.id
                    )
                    AS qtdDivergencia
                    ")
            ->addSelect("
                    (
                        SELECT COUNT(ie3.id)
                        FROM wms:Inventario\Endereco ie3
                        WHERE ie3.inventariado = 1
                        AND ie3.inventario = i.id
                    )
                    AS qtdInvetariado
                    ")
            ->from('wms:Inventario', 'i')
            ->innerJoin('i.status', 's')
            ->leftJoin("wms:Inventario\Endereco", 'ie', 'WITH', 'i.id = ie.inventario')
            ->groupBy('i, s.sigla')
            ->orderBy('i.id', 'DESC');

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
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
                'render' => 'DataTime'
            ))
            ->addColumn(array(
                'label' => 'Data Finalização',
                'index' => 'dataFinalizacao',
                'render' => 'DataTime'
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status'
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
