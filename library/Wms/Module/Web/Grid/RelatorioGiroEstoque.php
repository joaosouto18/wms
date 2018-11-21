<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid;

class RelatorioGiroEstoque extends Grid
{
    /**
     *
     * @param array $params 
     */

    public function init($params)
    {
        $this->setAttrib('title','Pedidos Expedicao');
        $this->setSource(new \Core\Grid\Source\ArraySource($params))
            ->setId('giro-produtos-grid')
            ->setAttrib('class', 'grid-giro-produto');

        $this->addColumn(array(
                'label' => 'Código do Produto',
                'index' => 'COD_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Descrição Produto',
                'index' => 'DSC_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'DSC_GRADE',
            ))
            ->addColumn(array(
                'label' => 'Endereço Picking',
                'index' => 'DSC_DEPOSITO_ENDERECO',
            ))
            ->addColumn(array(
                'label' => 'Data Movimentação',
                'index' => 'DATA_MOVIMENTACAO',
            ));

        return $this;
    }


}

