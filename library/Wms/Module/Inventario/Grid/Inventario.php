<?php

namespace Wms\Module\Inventario\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Module\Web\Grid;

class Inventario extends Grid
{

    public function init($data = array())
    {
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        /** @var Parametro $impInventERP */
        $impInventERP = $parametroRepo->findOneBy(array('constante' => 'IMPORTA_INVENTARIO'));
        $this->setAttrib('title','Inventario');
        $source = $em->getRepository('wms:Inventario')->getInventarios(null, $data);
        $this->setSource(new \Core\Grid\Source\ArraySource($source))
            ->setId('monitoramento-inventario');
        $this->setShowExport(false);
        $this->addMassAction('index/relatorio','Movimentações no Estoque (xls)');
        $this->addColumn(array(
                'label' => 'Inventário',
                'index' => 'id',
             ));
        if ($impInventERP->getValor() === 'S'){
            $this->addColumn(array(
                    'label' => 'Código no ERP',
                    'index' => 'codInvERP',
                ));
        }

        $this->addColumn(array(
                'label' => 'Qtd Endereços',
                'index' => 'qtdEndereco',
            ))
            ->addColumn(array(
                'label' => 'Qtd Divergência',
                'index' => 'qtdDivergencia',
            ))
            ->addColumn(array(
                'label' => 'Qtd Inventariado',
                'index' => 'qtdInventariado',
            ))
            ->addColumn(array(
                'label' => 'Dt. Início',
                'index' => 'dataInicio'
            ))
            ->addColumn(array(
                'label' => 'Dt. Finalização',
                'index' => 'dataFinalizacao'
            ))
            ->addColumn(array(
                'label' => 'Andamento (%)',
                'index' => 'andamento',
                'render' => 'N2',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status',
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
                'label' => 'Atualizar Estoque',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'atualizar',
                'cssClass' => '',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "CONCLUIDO" && $row['qtdInventariado'] > 0 && $row['qtdDivergencia'] == 0;
                },
            ))
            ->addAction(array(
                'label' => 'Cancelar',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'cancelar',
                'cssClass' => 'confirm',
                'title' => 'Cancelar inventário?.',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO" && $row['status'] != "CANCELADO";
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
            ->addAction(array(
                'label' => 'Exportar Inventario',
                'modelName' => 'inventario',
                'controllerName' => 'index',
                'actionName' => 'export-inventario-ajax',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] == "FINALIZADO";
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

        if ($impInventERP->getValor() === 'S') {
            $this->addAction(array(
                'label' => 'Vincular inventário do ERP',
                'title' => 'Vincula o número do inventário do ERP',
                'actionName' => 'view-vincular-cod-erp-ajax',
                'cssClass' => 'view-andamento dialogAjax',
                'pkIndex' => 'id'
            ));
        }

        return $this;
    }

}
