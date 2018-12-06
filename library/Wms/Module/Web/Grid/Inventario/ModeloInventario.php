<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 05/12/2018
 * Time: 15:41
 */

namespace Wms\Module\Web\Grid\InventarioNovo;


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
            ->addAction(array(
                'label' => 'Editar',
                'moduleName' => 'inventario-novo',
                'controllerName' => 'modelo-inventario',
                'actionName' => 'edit',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Excluir',
                'moduleName' => 'inventario-novo',
                'controllerName' => 'modelo-inventario',
                'actionName' => 'delete',
                'pkIndex' => 'id'
            ));

        return $this;
    }

}

