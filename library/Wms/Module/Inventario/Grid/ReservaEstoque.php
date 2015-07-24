<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class ReservaEstoque extends Grid
{

    public function init($reservas)
    {
        $this->setAttrib('title','Reserva de Estoque');
        $this->setSource(new \Core\Grid\Source\ArraySource($reservas));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Codigo',
                'index' => 'id',
            ))
            ->addColumn(array(
                'label' => 'Endereço com reserva de estoque',
                'index' => 'descricao',
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
