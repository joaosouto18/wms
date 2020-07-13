<?php
namespace Wms\Domain\Entity\RelatorioCustomizado;

use Doctrine\ORM\EntityRepository;

class RelatorioCustomizadoRepository extends EntityRepository
{
    public function getProdutosReportMock () {

        $reportEn = new RelatorioCustomizado();
        $reportEn->setTitulo("Relatório de Produtos");
        $reportEn->setQuery("
            SELECT COD_PRODUTO as Codigo, DSC_PRODUTO 
              FROM PRODUTO P 
             WHERE 1 = 1 :CodProduto :DscProduto
        ");

        $filter = array();
        $filter[] = array(
            'NOME_PARAM' => 'CodProduto',
            'DSC_TITULO' => 'Código',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'DSC_QUERY' => " AND P.COD_PRODUTO = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'DscProduto',
            'DSC_TITULO' => 'Descrição',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'DSC_QUERY' => " AND P.DSC_PRODUTO LIKE '%:value%' "
        );

        $sort = array();
        $sort[] = array(
            'DSC_TITULO' => 'Código ASC',
            'DSC_QUERY' => 'P.COD_PRODUTO ASC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Descrição ASC',
            'DSC_QUERY' => 'P.DSC_PRODUTO ASC'
        );

        $result = array(
            'reportEn' => $reportEn,
            'filter' => $filter,
            'sort' => $sort
        );

        return $result;
    }

}