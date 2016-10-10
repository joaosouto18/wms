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
                'index' => 'COD_PRODUTO'
            ))
            ->addColumn(array(
                'label' => 'Descrição',
                'index' => 'DESCRICAO',
            ))
            ->addColumn(array(
                'label' => 'Linha de separação',
                'index' => 'LINHA_SEPARACAO',
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
                'width' => 3
            ))
            ->addLogicalFeatured(
                function ($row){
                    $dt = date_create_from_format('d/m/Y', $row['VALIDADE']) ;
                    $now = date_create_from_format('d/m/Y', date('d/m/Y'));
                    return $dt <= $now;
                }
            )
        ;

        return $this;
    }
}
