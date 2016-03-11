<?php

namespace Wms\Module\Web\Grid\Recebimento;

use Wms\Domain\Entity\Expedicao\RecebimentoReentrega;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Reentrega extends Grid
{

    public function init(array $params = array())
    {
        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
        $resultSet = $recebimentoRepo->buscar($params);
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
                    'index' => 'dataCriacao',
                    'render' => 'Data',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                ))
                ->addAction(array(
                    'label' => 'Visualizar Ordem de Serviço',
                    'title' => 'Ordens de Serviço do Recebimento',
                    'actionName' => 'view-ordem-servico-ajax',
                    'cssClass' => 'view-ordem-servico dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

}

