<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class CorteTotal extends Grid
{

    public function init($produtos)
    {
        $this->setAttrib('title','Produtos para Corte');
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'COD_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'DSC_GRADE',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'DSC_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Pedidos',
                'index' => 'QTD_PEDIDOS',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Separar',
                'index' => 'QTD_SEPARAR',
            ))
            ->addAction(array(
                'label' => 'Cortar',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'confirma-corte-total-ajax',
                'cssClass' => 'dialogAjax',
                'pkIndex' => array('COD_PRODUTO','DSC_GRADE')
            ));
        return $this;
    }

}
