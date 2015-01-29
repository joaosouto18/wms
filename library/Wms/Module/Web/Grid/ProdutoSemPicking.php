<?php

namespace Wms\Module\Web\Grid;
          

use Wms\Module\Web\Grid;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class ProdutoSemPicking extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($values)
    {

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
        $result = $produtoRepo->relatorioProdutosSemPicking($values);


        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-index-grid')
                ->setAttrib('class', 'grid-expedicao')
                ->addColumn(array(
                    'label' => 'Picking',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label'  => 'Código',
                    'index'  => 'codigo',
                ))
                ->addColumn(array(
                    'label' => 'Área Armazenagem',
                    'index' => 'areaArmazenagem',
                ))
                ->addColumn(array(
                    'label' => 'Ativo',
                    'index' => 'ativo',
                ))

                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                ))
                ->setShowExport(true);
        return $this;
    }

}

