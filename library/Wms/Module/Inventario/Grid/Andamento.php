<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class Andamento extends Grid
{

    public function init($params)
    {
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->getEntityManager()->getRepository("wms:Inventario");
        $sumarioByRua = $inventarioRepo->getSumarioByRua($params);
        $this->setAttrib('title','Andamento');
        $this->setSource(new \Core\Grid\Source\ArraySource($sumarioByRua));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Rua',
                'index' => 'RUA'
             ))
            ->addColumn(array(
                'label' => 'Qtd Endereços',
                'index' => 'QTD_ENDERECOS',
            ))
            ->addColumn(array(
                'label' => 'Qtd End. Divergência',
                'index' => 'QTD_DIVERGENTE',
            ))
            ->addColumn(array(
                'label' => 'Qtd Inventariado',
                'index' => 'QTD_INVENTARIADO',
            ))
            ->addColumn(array(
                'label' => 'Qtd Pendente',
                'index' => 'QTD_PENDENTE',
            ))
            ->addColumn(array(
                'label' => '% Concluido',
                'index' => 'CONCLUIDO',
            ))
            ->addAction(array(
                'label' => 'Visualizar Conferência',
                'actionName' => 'view-rua-ajax',
                'cssClass' => 'inside-modal',
                'pkIndex' => 'RUA'
            ));

        return $this;
    }

}
