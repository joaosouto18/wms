<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid;

class ModeloSeparacao extends Grid
{
    /**
     *
     * @param array $values
     */
    public function init ($values)
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('modelo-separacao-grid')
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
                    'moduleName' => 'expedicao',
                    'controllerName' => 'modelo-separacao',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'modelo-separacao',
                    'actionName' => 'delete',
                    'pkIndex' => 'id'
                ));

        return $this;
    }

}

