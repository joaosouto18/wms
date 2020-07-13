<?php
namespace Wms\Domain\Entity\RelatorioCustomizado;

use Doctrine\ORM\EntityRepository;

class RelatorioCustomizadoRepository extends EntityRepository
{
    public function getProdutosReportMock () {

        $reportEn = new RelatorioCustomizado();
        $reportEn->setTitulo("Relatório de Produtos");
        $reportEn->setQuery("
            SELECT COD_PRODUTO as Codigo, DSC_PRODUTO as Descrição 
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
            'filters' => $filter,
            'sort' => $sort
        );

        return $result;
    }

    public function getRelatoriosDisponiveisMock() {
        $result = array();
        $result[] = array(
            'COD_RELATORIO' => '1',
            'DSC_TITULO' => 'Relatório de Produtos',
            'TIPO' => 'Relatórios Cadastrais'
        );
        $result[] = array(
            'COD_RELATORIO' => '3',
            'DSC_TITULO' => 'Expedições por Dia',
            'TIPO' => 'Relatórios de Expedição'
        );
        $result[] = array(
            'COD_RELATORIO' => '2',
            'DSC_TITULO' => 'Relatório de Clientes',
            'TIPO' => 'Relatórios Cadastrais'
        );

        return $result;
    }

}