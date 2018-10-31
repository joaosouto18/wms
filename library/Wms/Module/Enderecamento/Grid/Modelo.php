<?php

namespace Wms\Module\Enderecamento\Grid;
          

use Wms\Module\Web\Grid;

class Modelo extends Grid
{
    /**
     *
     * @param array $values
     */
    public function init ($values)
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('modelo-grid')
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
                    'moduleName' => 'enderecamento',
                    'controllerName' => 'modelo',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'moduleName' => 'enderecamento',
                    'controllerName' => 'modelo',
                    'actionName' => 'delete',
                    'pkIndex' => 'id'
                ));

        return $this;
    }

}

