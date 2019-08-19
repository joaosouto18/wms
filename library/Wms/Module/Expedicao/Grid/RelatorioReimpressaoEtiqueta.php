<?php

namespace Wms\Module\Expedicao\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class RelatorioReimpressaoEtiqueta extends Grid
{
    public function init(array $result = [])
    {
        $this->setAttrib('title','Motivos de reimpressão')
            ->setShowPager(false)
            ->setShowExport(false)
            ->setSource(new \Core\Grid\Source\ArraySource($result))
            ->addColumn(array(
                'label' => 'Etiqueta',
                'index' => 'Etiqueta',
            ))
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'Expedição',
            ))
            ->addColumn(array(
                'label' => 'Carga',
                'index' => 'Carga',
            ))
            ->addColumn(array(
                'label' => 'Pedido',
                'index' => 'Pedido',
            ))
            ->addColumn(array(
                'label' => 'Cliente',
                'index' => 'Cliente',
            ))
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'Código',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'Produto',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'Grade',
            ))
            ->addColumn(array(
                'label' => 'Volume',
                'index' => 'Volume'
            ))
            ->addColumn(array(
                'label' => 'Motivo',
                'index' => 'Motivo'
            ));

        return $this;
    }

}

