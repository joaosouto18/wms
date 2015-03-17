<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;

/**
 * Description of DadoLogistico
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Recebimento extends Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');
        $resultSet = $recebimentoRepo->buscar($params);

        $this->setSource(new \Core\Grid\Source\ArraySource($resultSet))
                ->setId('recebimento-index-grid')
                ->setAttrib('class', 'grid-recebimento')
                ->addColumn(array(
                    'label' => 'Código do Recebimento',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Data Inicial',
                    'index' => 'dataInicial',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'dataFinal',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                ))
                ->addColumn(array(
                    'label' => 'Box',
                    'index' => 'dscBox'
                ))
                ->addColumn(array(
                    'label' => 'Fornecedor',
                    'index' => 'fornecedor',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Nota Fiscal',
                    'index' => 'qtdNotaFiscal',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Produtos',
                    'index' => 'qtdProduto',
                ))
                ->addAction(array(
                    'label' => 'Iniciar Recebimento',
                    'actionName' => 'iniciar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['idStatus'] == RecebimentoEntity::STATUS_CRIADO;
                    }
                ))
                ->addAction(array(
                    'label' => 'Digitação da Conferência Cega',
                    'actionName' => 'conferencia',
                    'pkIndex' => 'idOrdemServico',
                    'condition' => function ($row) {
                        return (($row['idStatus'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA) && $row['idOrdemServicoManual']);
                    }
                ));


            $this
                    ->addAction(array(
                            'label' => 'Descarga Recebimento',
                            'actionName' => 'index',
                            'moduleName' => 'produtividade',
                            'controllerName' => 'descarga',
                            'pkIndex' => 'id',
                            'condition' => function ($row) {
                                return (($row['idStatus'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA) && $row['idOrdemServicoManual']);
                            }
                        ));


            $this
                ->addAction(array(
                            'label' => 'Descarga Recebimento',
                            'actionName' => 'index',
                            'moduleName' => 'produtividade',
                            'controllerName' => 'descarga',
                            'pkIndex' => 'id',
                            'condition' => function ($row) {
                                return (($row['idStatus'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA) && $row['idOrdemServicoManual']);
                            }
                 ))->addAction(array(
                    'label' => 'Finalizar Conferência Coletor',
                    'actionName' => 'conferencia-coletor-ajax',
                    'pkIndex' => 'idOrdemServico',
                    'condition' => function ($row) {
                        return (($row['idStatus'] == RecebimentoEntity::STATUS_CONFERENCIA_COLETOR) && $row['idOrdemServicoColetor']);
                    }
                ))
                ->addAction(array(
                    'label' => 'Visualizar Ordem de Serviço',
                    'title' => 'Ordens de Serviço do Recebimento',
                    'actionName' => 'view-ordem-servico-ajax',
                    'cssClass' => 'view-ordem-servico dialogAjax',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['idStatus'] != RecebimentoEntity::STATUS_CRIADO;
                    }
                ))
                ->addAction(array(
                    'label' => 'Relatório de conferência cega',
                    'title' => 'Relatório de conferência cega',
                    'actionName' => 'conferencia-cega-pdf',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['idStatus'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA;
                    }
                ))
                ->addAction(array(
                    'label' => 'Visualizar Andamento',
                    'title' => 'Andamento do Recebimento',
                    'actionName' => 'view-andamento-ajax',
                    'cssClass' => 'view-andamento dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Notas Fiscais',
                    'title' => 'Notas do Recebimento',
                    'actionName' => 'view-nota-item-ajax',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Gerar Ordem Servico Conf. Cega',
                    'actionName' => 'conferencia-cega',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return ($row['idStatus'] == RecebimentoEntity::STATUS_INICIADO);
                    }
                ))
                ->addAction(array(
                    'label' => 'Imprimir Etiquetas dos Produtos',
                    'title' => 'Imprimir Etiquetas dos Produtos com Código de Barras Automático',
                    'actionName' => 'gerar-etiqueta-pdf',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id',
                    'target' => '_blank',
                ))
                ->addAction(array(
                    'label' => 'Produtos Sem Dados Logisticos',
                    'title' => 'Relatório de Produtos Sem Dados Logisticos',
                    'actionName' => 'produtos-sem-dados-logisticos-pdf',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id',
                    'target' => '_blank',
                ))
                ->addAction(array(
                    'label' => 'Desfazer Recebimento',
                    'actionName' => 'desfazer',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return (!in_array($row['idStatus'], array(
                                    RecebimentoEntity::STATUS_CANCELADO,
                                    RecebimentoEntity::STATUS_FINALIZADO,
                                    RecebimentoEntity::STATUS_DESFEITO,
                                        )
                                ));
                    }
                ))
                ->addAction(array(
                    'label' => 'Endereçamento',
                    'moduleName' => 'enderecamento',
                    'actionName' => 'index',
                    'controllerName' => "produto",
                    'pkIndex' => 'id'
                ))
                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

}

