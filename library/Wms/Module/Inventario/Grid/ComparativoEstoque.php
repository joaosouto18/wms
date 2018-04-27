<?php

namespace Wms\Module\Inventario\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class ComparativoEstoque extends Grid {

    public function init($restult) {

        $this->setAttrib('title', 'comparativo-estoque');
        $this->setSource(new \Core\Grid\Source\ArraySource($restult));
        $this->addColumn(array(
            'label' => 'Cod. Produto',
            'index' => 'COD_PRODUTO',
        ));
        $this->addColumn(array(
            'label' => 'Grade',
            'index' => 'DSC_GRADE',
        ));
        $this->addColumn(array(
            'label' => 'Produto',
            'index' => 'DSC_PRODUTO',
        ));
        $this->addColumn(array(
            'label' => 'Estoque ERP',
            'index' => 'ESTOQUE_ERP',
            'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Estoque WMS',
            'index' => 'ESTOQUE_WMS',
            'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Divergência',
            'index' => 'DIVERGENCIA',
                    'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.WMS',
            'index' => 'VLR_ESTOQUE_WMS',
            'render' => 'N2'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.ERP',
            'index' => 'VLR_ESTOQUE_ERP',
            'render' => 'N2'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.Div.',
            'index' => 'VLR_DIVERGENCIA',
            'render' => 'N2'
        ));

        $this->setShowExport(false)
                ->addMassAction('mass-select', 'Selecionar');
        $pg = new Pager(count($restult), 0, count($restult));
        $this->setPager($pg);
        return $this;
    }

}
