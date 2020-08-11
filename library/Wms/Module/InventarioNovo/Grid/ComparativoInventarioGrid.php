<?php

namespace Wms\Module\InventarioNovo\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class ComparativoInventarioGrid extends Grid
{

    public function init($arr)
    {
        $this->setAttrib('title','Produtos');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->addColumn(array(
                'label' => 'Cód.Produto',
                'index' => 'COD_PRODUTO'));
        $this->addColumn(array(
            'label' => 'Grade',
            'index' => 'DSC_GRADE'));
        $this->addColumn(array(
            'label' => 'Descrição',
            'index' => 'DSC_PRODUTO'));

        if (count($arr) >0) {
            if (isset($arr[0]['QTD'])) {
                $this->addColumn(array(
                    'label' => 'Quantidade',
                    'index' => 'QTD',
                ));
            }
        }

        $pager = new Pager((count($arr)),0,count($arr));
        $this->setPager($pager);

        $this->showExport = false;

        return $this;
    }

}
