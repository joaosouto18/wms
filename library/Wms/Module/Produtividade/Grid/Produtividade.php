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
                    'index' => 'OPERACAO',
                ))
                ->addColumn(array(
                    'label' => 'Usuario',
                    'index' => 'NOM_PESSOA',
                ))
                ->addColumn(array(
                    'label' => 'Data de execução',
                    'index' => 'DTH_ATIVIDADE',
                ))
                ->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'PESO',
                ))
                ->addColumn(array(
                    'label' => 'Cubagem',
                    'index' => 'CUBAGEM',
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

