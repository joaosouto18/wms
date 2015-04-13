<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class ReservaEstoque extends Grid
{

    public function init($reservas)
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($reservas));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Endereço com reserva de estoque',
                'index' => 'id',
            ))
            ->addMassAction('mass-select', 'Remover endereço do inventário');

        return $this;
    }

}
