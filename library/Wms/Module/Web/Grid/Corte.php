<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\ProdutoRepository;

/**
 * Descrição: Classe destinada para exibir os resultados
 * no Grid para listar todos os cortes por dia / produto
 *
 * @author Diogo Marcos <contato@diogomarcos.com>
 */
class Corte extends Grid
{
    /**
     * @param array $params
     */
    public function init(array $params = array())
    {
        /** @var ProdutoRepository $produtoRepo */
        $produtoRepo =  $this->getEntityManager()->getRepository("wms:Produto");
        $result = $produtoRepo->getCortePorDiaProduto($params);

        $this->setAttrib('title','Cortes por Dia/Produto');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
            ->setId('expedicao-index-grid')
            ->setAttrib('class', 'grid-expedicao')
            ->addColumn(array(
                'label' => 'Data Início',
                'index' => 'DTH_INICIO',
            ))
            ->addColumn(array(
                'label' => 'Cód.Expedição',
                'index' => 'COD_EXPEDICAO',
            ))
            ->addColumn(array(
                'label' => 'Cód.Carga',
                'index' => 'COD_CARGA',
            ))
            ->addColumn(array(
                'label' => 'Cód.Produto',
                'index' => 'COD_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'DSC_GRADE',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'DSC_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Produtos',
                'index' => 'QUANTIDADE',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Cortada',
                'index' => 'QTD_CORTADA',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Atendida',
                'index' => 'QTD_ATENDIDA',
            ))
            ->addColumn(array(
                'label' => 'Tipo Corte',
                'index' => 'TIPO_CORTE',
            ))
            ->setShowExport(true);

        return $this;
    }
}