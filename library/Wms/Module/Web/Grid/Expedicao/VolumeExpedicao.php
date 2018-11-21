<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class VolumeExpedicao extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($values)
    {
        $this->setAttrib('title','Volumes Expedição');
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('expedicao-produto-volume-grid')
                ->setAttrib('caption', 'Volumes Patrimonios usados na expedição')
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
                    'label' => 'Situação',
                    'index' => 'situacao',
                ))
                ->setShowExport(false);
        return $this;
    }

}

