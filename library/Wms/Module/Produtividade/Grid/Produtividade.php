<?php

namespace Wms\Module\Produtividade\Grid;

use Wms\Module\Web\Grid;

class Produtividade extends Grid
{
    public function init($params)
    {

        $this->setAttrib('title','apontamento-separacao');
        $this->setSource(new \Core\Grid\Source\ArraySource($params))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'DSC_ATIVIDADE',
                ))
                ->addColumn(array(
                    'label' => 'Usuario',
                    'index' => 'NOM_PESSOA',
                ))
                ->addColumn(array(
                    'label' => 'Volumes',
                    'index' => 'QTD_VOLUMES',
                ))
                ->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'QTD_PESO',
                ))
                ->addColumn(array(
                    'label' => 'Cubagem',
                    'index' => 'QTD_CUBAGEM',
                ))
                ->addColumn(array(
                    'label' => 'Produtos',
                    'index' => 'QTD_PRODUTOS',
                ))
                ->addColumn(array(
                    'label' => 'Paletes',
                    'index' => 'QTD_PALETES',
                ));

        $this->setShowExport(false);

        return $this;
    }

}

