<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class EmpecilhosGrid extends Grid
{

    public function init($arr)
    {
        $this->setAttrib('title','Reserva de Estoque');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Codigo',
                'index' => 'id',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'produto',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'descricao',
            ))
            ->addColumn(array(
                'label' => 'Operação',
                'index' => 'origemReserva',
            ))
            ->addColumn(array(
                'label' => 'Pedido',
                'index' => 'pedido',
            ))
            ->addColumn(array(
                'label' => 'Tipo reserva',
                'index' => 'tipoReserva',
            ))
            ->addColumn(array(
                'label' => 'Data reserva',
                'index' => 'dataReserva',
                'render'=> 'DataTime'
            ))
            ->addMassAction('mass-select', 'Remover endereço do inventário');

        return $this;
    }

}
