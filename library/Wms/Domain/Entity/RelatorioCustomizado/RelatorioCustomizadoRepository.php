<?php
namespace Wms\Domain\Entity\RelatorioCustomizado;

use Doctrine\ORM\EntityRepository;

class RelatorioCustomizadoRepository extends EntityRepository
{
    public function getFilterContent($query) {
        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $arrResult = array();
        foreach ($result as $r) {
            $arrResult[$r['VALUE']] = $r['LABEL'];
        }
        return $arrResult;
    }

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
            'PARAMS' => '',
            'DSC_QUERY' => " AND P.COD_PRODUTO = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'DscProduto',
            'DSC_TITULO' => 'Descrição',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'PARAMS' => '',
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

    public function getExpedicaoReportMock () {

        $reportEn = new RelatorioCustomizado();
        $reportEn->setTitulo("Relatório de Expedição");
        $reportEn->setQuery("
                SELECT E.COD_EXPEDICAO as EXPEDICAO, 
                       E.DSC_PLACA_EXPEDICAO as PLACA, 
                       TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY') as DTH_INICIO,
                       TO_CHAR(E.DTH_FINALIZACAO,'DD/MM/YYYY') as DTH_FINAL,
                       S.DSC_SIGLA as SITUACAO
                  FROM EXPEDICAO E
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                 WHERE E.DTH_INICIO >= TO_DATE('10-06-2020 00:00','DD-MM-YYYY HH24:MI') 
                       :CodExpedicao :DthInicio :Situacao
        ");

        $filter = array();
        $filter[] = array(
            'NOME_PARAM' => 'CodExpedicao',
            'DSC_TITULO' => 'Expedição',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'PARAMS' => '',
            'DSC_QUERY' => " AND E.COD_EXPEDICAO = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'DthInicio',
            'DSC_TITULO' => 'Data de Inicio',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'Date',
            'PARAMS' => '',
            'DSC_QUERY' => " AND E.DTH_INICIO >= TO_DATE(':value 00:00','DD/MM/YYYY HH24:MI') "
        );
        $filter[] = array(
            'NOME_PARAM' => 'Situacao',
            'DSC_TITULO' => 'Situação',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'SQL',
            'PARAMS' => 'SELECT COD_SIGLA as VALUE, DSC_SIGLA as LABEL FROM SIGLA WHERE COD_TIPO_SIGLA = 53',
            'DSC_QUERY' => " AND E.COD_STATUS = ':value' "
        );
        $sort = array();
        $sort[] = array(
            'DSC_TITULO' => 'Código ASC',
            'DSC_QUERY' => 'E.COD_EXPEDICAO ASC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Código DSC',
            'DSC_QUERY' => 'E.COD_EXPEDICAO DESC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Dt. Inicio ASC',
            'DSC_QUERY' => 'E.DTH_INICIO ASC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Dt. Inicio DSC',
            'DSC_QUERY' => 'E.DTH_INICIO DESC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Dt. Finalização ASC',
            'DSC_QUERY' => 'E.DTH_FINALIZACAO ASC'
        );
        $sort[] = array(
            'DSC_TITULO' => 'Dt. Finalização DSC',
            'DSC_QUERY' => 'E.DTH_FINALIZACAO DESC'
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