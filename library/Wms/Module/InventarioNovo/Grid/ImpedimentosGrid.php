<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class ImpedimentosGrid extends Grid
{

    public function init($arr)
    {
        $this->setAttrib('title','Reserva de Estoque');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->setShowExport(true);
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
            ->addAction(array(
                'label' => 'Remover este endereço',
                'moduleName' => 'inventario_novo',
                'controllerName' => 'index',
                'actionName' => 'remover-endereco',
                'cssClass' => 'del',
                'pkIndex' => 'idEndereco',
                'condition' => function ($row) {
                    return $row['criterio'] == "E";
                }
            ))
            ->addAction(array(
                'label' => 'Remover este produto',
                'moduleName' => 'inventario_novo',
                'controllerName' => 'index',
                'actionName' => 'remover-produto',
                'cssClass' => 'del',
                'pkIndex' => ['produto', 'grade', 'idEndereco', 'lote'],
                'condition' => function ($row) {
                    return $row['criterio'] == "P";
                }
            ));

        return $this;
    }

}
