<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class EnderecosGrid extends Grid
{

    public function init($arr)
    {
        $this->setAttrib('title','Endereços deste inventário');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->setShowExport(false);
        $this->setHiddenId("remover");
        $this
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'descricao',
            ))->addColumn(array(
                'label' => 'Situação',
                'index' => 'status',
            ))
            ->addMassAction('index/remover-endereco', "Remover Endereços");

        return $this;
    }

}
