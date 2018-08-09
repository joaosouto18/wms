<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class PedidosCorteTotal extends Grid
{

    public function init($produtos)
    {
        $this->setAttrib('title','Pedidos para Cortar');
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Pedido',
                'index' => 'COD_PEDIDO',
            ))
            ->addColumn(array(
                'label' => 'Cód.Cliente',
                'index' => 'COD_CLIENTE',
            ))
            ->addColumn(array(
                'label' => 'Cliente',
                'index' => 'CLIENTE',
            ))
            ->addColumn(array(
                'label' => 'Qtd.',
                'index' => 'QTD',
            ));
        return $this;
    }

}
