<?php

namespace Wms\Module\InventarioNovo\Grid;
          

use Wms\Module\Web\Grid;

class ModeloInventario extends Grid
{
    /**
     *
     * @param array $values
     */
    public function init ($values)
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('modelo-inventario-grid')
                ->addColumn(array(
                    'label'  => 'Código',
                    'index'  => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Data de criação',
                    'index' => 'dthCriacao',
                    'render' => 'Data',
                ))
                ->addColumn(array(
                    'label'  => 'Default',
                    'index'  => 'default',
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'moduleName' => 'inventario_novo',
                    'controllerName' => 'modelo-inventario',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'moduleName' => 'inventario_novo',
                    'controllerName' => 'modelo-inventario',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del'
                ));

        return $this;
    }

}

