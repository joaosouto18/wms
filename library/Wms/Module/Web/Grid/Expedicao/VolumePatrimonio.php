<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class VolumePatrimonio extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($values, $showAction = true)
    {
        $this->setAttrib('title','Volume Patrimonio');
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('expedicao-produto-volume-grid')
                ->setAttrib('caption', 'Andamento da expedição')
                ->setAttrib('class', 'grid-produto-volume')
                ->addColumn(array(
                    'label'  => 'Código',
                    'index'  => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Ocupado',
                    'render' => 'SimOrNao',
                    'index' => 'ocupado',
                ))
                ->addColumn(array(
                    'label' => 'Expedição',
                    'index' => 'expedicao',
                ))
                ->setShowExport(false);

        if ($showAction == true) {
            $this
            ->addAction(array(
                'label' => 'Excluir',
                'moduleName' => 'expedicao',
                'controllerName' => 'volume-patrimonio',
                'actionName' => 'delete',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Imprimir etiqueta',
                'moduleName' => 'expedicao',
                'cssClass' => 'pdf',
                'controllerName' => 'volume-patrimonio',
                'actionName' => 'imprimir',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Desocupar',
                'moduleName' => 'expedicao',
                'controllerName' => 'volume-patrimonio',
                'actionName' => 'desfazer',
                'condition' => function ($row) {
                        return $row['ocupado'] == "S";
                 },
                'pkIndex' => array('id','expedicao'),
            ));
        }

        return $this;
    }

}

