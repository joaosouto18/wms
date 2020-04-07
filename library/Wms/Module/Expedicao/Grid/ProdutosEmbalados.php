<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class ProdutosEmbalados extends Grid
{

    public function init($produtos)
    {
        $this->setAttrib('title','Produtos Pertencentes ao Volume Embalado');
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos));
        $this->setShowExport(false);
        $this
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'codProduto',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'descricao',
            ))
            ->addColumn(array(
                'label' => 'Quantidade',
                'index' => 'quantidade',
            ));

        return $this;
    }

}
