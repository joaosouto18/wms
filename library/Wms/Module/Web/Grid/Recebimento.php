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
        $resultSet = $recebimentoRepo->searchNew($params);
        $this->setAttrib('title','Recebimento');
        $this->setSource(new \Core\Grid\Source\ArraySource($resultSet))
                ->setId('recebimento-index-grid')
                ->setAttrib('class', 'grid-recebimento')
                ->addColumn(array(
                    'label' => 'Código do Recebimento',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Data Inicial',
                    'index' => 'DATAINICIAL',
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'DATAFINAL',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'STATUS',
                ))
                ->addColumn(array(
                    'label' => 'Box',
                    'index' => 'DSCBOX'
                ))
                ->addColumn(array(
                    'label' => 'Fornecedor',
                    'index' => 'FORNECEDOR',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Nota Fiscal',
                    'index' => 'QTDNOTAFISCAL',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Caixas',
                    'index' => 'QTDMAIOR',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Fracões',
                    'index' => 'QTDMENOR',
                ))
                ->addAction(array(
                    'label' => 'Iniciar Recebimento',
                    'actionName' => 'iniciar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['IDSTATUS'] == RecebimentoEntity::STATUS_CRIADO;
                    }
                ))
                ->addAction(array(
                    'label' => 'Digitação da Conferência Cega',
                    'actionName' => 'conferencia',
                    'pkIndex' => 'idOrdemServico',
                    'condition' => function ($row) {
                        return (($row['IDSTATUS'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA) && $row['IDORDEMSERVICOMANUAL']);
                    }
                ));

            $this
                
                ->addAction(array(
                    'label' => 'Funcionário Descarga',
                    'title' => 'Funcionários que descarregaram o Recebimento',
                    'actionName' => 'usuario-recebimento-pdf',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id',
                    'target' => '_blank',
                ))

                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

}

