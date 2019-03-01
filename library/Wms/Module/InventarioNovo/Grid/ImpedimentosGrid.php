<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class ImpedimentosGrid extends Grid
{

    public function init($arr, $direction)
    {
        $this->setAttrib('title','Impedimentos');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'descricao',
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
                'label' => 'Operação',
                'index' => 'origemImpedimento',
            ))
            ->addColumn(array(
                'label' => 'Data da Operação',
                'index' => 'dataOperacao'
            ))
            ->addMassAction("../../../$direction", "Remover");

        return $this;
    }

}
