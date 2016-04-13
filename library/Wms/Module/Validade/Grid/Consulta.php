<?php

namespace Wms\Module\Validade\Grid;

use Wms\Module\Web\Grid;

class Consulta extends Grid
{

    public function init($params)
    {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $produto = $produtoRepo->getProdutoByParametroVencimento($params);
        $this->setAttrib('title','Consulta');
        $this->setSource(new \Core\Grid\Source\ArraySource($produto));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Cód. Produto',
                'index' => 'codProduto'
             ))
            ->addColumn(array(
                'label' => 'Descrição',
                'index' => 'produto',
            ))
            ->addColumn(array(
                'label' => 'Fornecedor',
                'index' => 'fornecedor',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'endereco',
            ))
            ->addColumn(array(
                'label' => 'Validade',
                'index' => 'validade',
                'render'=> 'DataTime',
            ))
            ->addColumn(array(
                'label' => 'Quantidade (Por Endereço)',
                'index' => 'qtd',
            ))
//            ->addAction(array(
//                'label' => 'exportar para pdf',
//                'actionName' => 'export-pdf',
//                'cssClass' => 'inside-modal',
//                'pkIndex' => 'codProduto'
//            ))
            ;

        return $this;
    }

}
