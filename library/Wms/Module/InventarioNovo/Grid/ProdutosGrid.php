<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class ProdutosGrid extends Grid
{

    public function init($arr)
    {
        $this->setAttrib('title','Produtos deste inventário');
        $this->setSource(new \Core\Grid\Source\ArraySource($arr));
        $this->setShowExport(false);
        $this->setHiddenId("remover");
        $this
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'codProduto',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'descricao',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'dscEndereco',
            ))
            ->addColumn(array(
                'label' => 'Situação do Endereço',
                'index' => 'status',
            ))
            ->addMassAction('index/remover-produto', "Remover Produtos");

        return $this;
    }

}
