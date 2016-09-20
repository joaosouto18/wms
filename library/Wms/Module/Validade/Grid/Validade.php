<?php

namespace Wms\Module\Validade\Grid;

use Wms\Module\Web\Grid;

class Validade extends Grid
{

    public function init($produtos)
    {
        $this->setAttrib('title','Consulta');
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos));
        $this->setShowExport(false);
        $this->addColumn(array(
            'label' => 'Cód. Produto',
            'index' => 'CODPRODUTO'
        ))
            ->addColumn(array(
                'label' => 'Descrição',
                'index' => 'PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Linha de separação',
                'index' => 'LINHASEPARACAO',
            ))
            ->addColumn(array(
                'label' => 'Fornecedor',
                'index' => 'FORNECEDOR',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'ENDERECO',
            ))
            ->addColumn(array(
                'label' => 'Validade',
                'index' => 'VALIDADE',
            ))
            ->addColumn(array(
                'label' => 'Qtd',
                'index' => 'QTD',
            ))
        ;

        return $this;
    }
}
