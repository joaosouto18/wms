<?php

namespace Wms\Module\Produtividade\Grid;

use Core\Grid\Pager;
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

        $this->setShowExport(true);
        $pg = new Pager(count($params),0,count($params));
        $this->setPager($pg);
        return $this;
    }

}

