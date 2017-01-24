<?php

namespace Wms\Module\Produtividade\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class Produtividade extends Grid
{
    public function init($params, $sort)
    {

        $this->setAttrib('title','apontamento-separacao');
        $this->setSource(new \Core\Grid\Source\ArraySource($params));
                if ($sort == 'atividade') {
                    $this->addColumn(array(
                        'label' => 'Atividade',
                        'index' => 'DSC_ATIVIDADE',
                    ));
                    $this->addColumn(array(
                        'label' => 'Usuario',
                        'index' => 'NOM_PESSOA',
                    ));
                } else {
                    $this->addColumn(array(
                        'label' => 'Usuario',
                        'index' => 'NOM_PESSOA',
                    ));
                    $this->addColumn(array(
                        'label' => 'Atividade',
                        'index' => 'DSC_ATIVIDADE',
                    ));
                }

                $this->addColumn(array(
                    'label' => 'Volumes',
                    'index' => 'QTD_VOLUMES',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'QTD_PESO',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Cubagem',
                    'index' => 'QTD_CUBAGEM',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Produtos',
                    'index' => 'QTD_PRODUTOS',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Ruas',
                    'index' => 'QTD_RUA',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Paletes',
                    'index' => 'QTD_PALETES',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Cargas',
                    'index' => 'QTD_CARGA',
                    'render' => 'N2'
                ));

        $this->setShowExport(true);
        $pg = new Pager(count($params),0,count($params));
        $this->setPager($pg);
        return $this;
    }

}

