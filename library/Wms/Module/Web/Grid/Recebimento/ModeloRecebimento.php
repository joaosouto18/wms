<?php

namespace Wms\Module\Web\Grid\Recebimento;
          

use Wms\Module\Web\Grid;

class ModeloRecebimento extends Grid
{
    /**
     *
     * @param array $values
     */
    public function init ($values)
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('modelo-recebimento-grid')
                ->addColumn(array(
                    'label'  => 'Código',
                    'index'  => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Validade',
                    'index' => 'controleValidade',
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'moduleName' => 'web',
                    'controllerName' => 'recebimento',
                    'actionName' => '',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'moduleName' => 'web',
                    'controllerName' => 'recebimento',
                    'actionName' => '',
                    'pkIndex' => 'id'
                ));

        return $this;
    }

}

