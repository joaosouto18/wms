<?php

namespace Wms\Module\Enderecamento\Grid;

use Wms\Module\Web\Grid;

class DisponibilidadeEndereco extends Grid
{

    public function init($resource)
    {
        $this->setAttrib('title','Disponibilidade de Endereços');
        $this->setSource(new \Core\Grid\Source\ArraySource($resource));
        $this->setShowExport(false);
        $this->addColumn(array(
            'label' => 'Endereço',
            'index' => 'descricao',
            'width' => '10%'
        ))
        ->addColumn(array(
            'label' => 'Código',
            'index' => 'codProduto'
        ))
        ->addColumn(array(
            'label' => 'Grade',
            'index' => 'grade',
            'width' => '15%'
        ))
        ->addColumn(array(
            'label' => 'Quantidade',
            'index' => 'qtd',
            'width' => '10%'
        ))
        ->addColumn(array(
            'label' => 'Situação',
            'index' => 'statusEndereco'
        ));

        return $this;
    }

}
