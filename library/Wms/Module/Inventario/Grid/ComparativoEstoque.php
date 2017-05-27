<?php

namespace Wms\Module\Inventario\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class ComparativoEstoque extends Grid
{
    public function init($restult)
    {

        $this->setAttrib('title','comparativo-estoque');
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
                    'label' => 'Estoqe WMS',
                    'index' => 'ESTOQUE_WMS',
                    'render' => 'N3'
                ));
                $this->addColumn(array(
                    'label' => 'Divergência',
                    'index' => 'DIVERGENCIA',
//                    'render' => 'N3'
                ));
        $this->setShowExport(false);
        $pg = new Pager(count($restult),0,count($restult));
        $this->setPager($pg);
        return $this;
    }

}

