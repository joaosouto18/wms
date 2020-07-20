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

    public function executeQuery($query, $conexaoEn) {
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->getEntityManager()->getRepository('wms:Integracao\ConexaoIntegracao');

        if ($conexaoEn == null) {
            $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = $conexaoRepo->runQuery($query, $conexaoEn, false);
        }

        return $result;
    }

    public function getProdutosReportMock () {

        $reportEn = new RelatorioCustomizado();
        $reportEn->setTitulo("Relatório de Produtos");
        $reportEn->setConexao(null);
        $reportEn->setAllowPDF("S");
        $reportEn->setAllowSearch("S");
        $reportEn->setAllowXLS("S");
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
            'TAMANHO' => '',
            'DSC_QUERY' => " AND P.COD_PRODUTO = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'DscProduto',
            'DSC_TITULO' => 'Descrição',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'PARAMS' => '',
            'TAMANHO' => '',
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
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->getEntityManager()->getRepository('wms:Integracao\ConexaoIntegracao');
        $conexaoEn = $conexaoRepo->find(3);

        $reportEn = new RelatorioCustomizado();
        $reportEn->setTitulo("Relatório de Expedição");

        $reportEn->setConexao($conexaoEn);
        $reportEn->setAllowPDF("N");
        $reportEn->setAllowSearch("S");
        $reportEn->setAllowXLS("N");
        $reportEn->setQuery("
                SELECT E.COD_EXPEDICAO as EXPEDICAO, 
                       E.DSC_PLACA_EXPEDICAO as PLACA, 
                       TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY') as DTH_INICIO,
                       TO_CHAR(E.DTH_FINALIZACAO,'DD/MM/YYYY') as DTH_FINAL,
                       S.DSC_SIGLA as SITUACAO
                  FROM EXPEDICAO E
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                 WHERE E.DTH_INICIO >= TO_DATE('10-06-2020 00:00','DD-MM-YYYY HH24:MI') 
                       :CodExpedicao :DthInicio :Situacao :Finalizado
        ");

        $filter = array();
        $filter[] = array(
            'NOME_PARAM' => 'CodExpedicao',
            'DSC_TITULO' => 'Expedição',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'text',
            'PARAMS' => '',
            'TAMANHO' => '8',
            'DSC_QUERY' => " AND E.COD_EXPEDICAO = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'DthInicio',
            'DSC_TITULO' => 'Data de Inicio',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'Date',
            'PARAMS' => '',
            'TAMANHO' => '',
            'DSC_QUERY' => " AND E.DTH_INICIO >= TO_DATE(':value 00:00','DD/MM/YYYY HH24:MI') "
        );
        $filter[] = array(
            'NOME_PARAM' => 'Situacao',
            'DSC_TITULO' => 'Situação',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'SQL',
            'PARAMS' => 'SELECT COD_SIGLA as VALUE, DSC_SIGLA as LABEL FROM SIGLA WHERE COD_TIPO_SIGLA = 53',
            'TAMANHO' => '',
            'DSC_QUERY' => " AND E.COD_STATUS = ':value' "
        );
        $filter[] = array(
            'NOME_PARAM' => 'Finalizado',
            'DSC_TITULO' => 'Finalizado',
            'IND_OBRIGATORIO' => 'N',
            'TIPO' => 'select',
            'TAMANHO' => '',
            'PARAMS' => '{"E.COD_STATUS = 465":"Sim","E.COD_STATUS <> 465":"Nao"}',
            'DSC_QUERY' => " AND :value "
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