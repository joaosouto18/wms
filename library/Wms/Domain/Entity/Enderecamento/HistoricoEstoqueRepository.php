<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;

class HistoricoEstoqueRepository extends EntityRepository
{
     public function getMovimentacaoProduto($parametros)
     {
         $query = $this->getEntityManager()->createQueryBuilder()
             ->select("hist.codProduto,
                       hist.grade,
                       hist.observacao,
                       hist.qtd,
                       hist.data data,
                       dep.descricao,
                       prod.descricao nomeProduto,
                       usu.login nomePessoa,
                       un.id as Unitizador,
                       NVL(vol.descricao, 'PRODUTO UNITÁRIO') as volume,
                       un.descricao as Norma")
             ->from("wms:Enderecamento\HistoricoEstoque",'hist')
             ->innerJoin("hist.produto", "prod")
             ->innerJoin("hist.depositoEndereco", "dep")
             ->innerJoin("hist.usuario", "usu")
             ->leftJoin("hist.unitizador", "un")
             ->leftJoin("hist.produtoVolume","vol")
             ->groupBy("hist.codProduto,
                        hist.grade,
                        hist.observacao,
                        hist.qtd,
                        hist.data,
                        dep.descricao,
                        prod.descricao,
                        usu.login,
                        un.id,
                        vol.descricao,
                        un.descricao");

         if (isset($parametros['idProduto']) && !empty($parametros['idProduto'])) {
             $query->andWhere("hist.codProduto = '$parametros[idProduto]'");
         }
         if (isset($parametros['grade']) && !empty($parametros['grade'])) {
             $query->andWhere("hist.grade = '$parametros[grade]'");
         }
         if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
             $query->andWhere("dep.nivel = '$parametros[nivel]'");
         }
         if (isset($parametros['rua']) && !empty($parametros['rua'])) {
             $query->andWhere("dep.rua = '$parametros[rua]'");
         }
         if (isset($parametros['predio']) && !empty($parametros['predio'])) {
             $query->andWhere("dep.predio = " . $parametros['predio']);
         }
         if (isset($parametros['apto']) && !empty($parametros['apto'])) {
             $query->andWhere("dep.apartamento = " . $parametros['apto']);
         }
         if (isset($parametros['dataInicial']) && (!empty($parametros['dataInicial'])) && (!empty($parametros['dataFim'])))
         {
             $dataInicial = str_replace("/", "-", $parametros['dataInicial']);
             $dataI = new \DateTime($dataInicial);

             $dataFim = str_replace("/", "-", $parametros['dataFim']);
             $dataF = new \DateTime($dataFim);

             $query->andWhere("(((TRUNC(hist.data) >= ?1) AND (TRUNC(hist.data)) <= ?2)  OR hist.data IS NULL)")
                 ->setParameter(1, $dataI)
                 ->setParameter(2, $dataF);
         }

         $query->orderBy("hist.codProduto, hist.grade, vol.descricao, hist.data");

         $resultado = $query->getQuery()->getResult();

         return $resultado;
     }

    public function getDadosMovimentacao($parametros)
    {
         $dataInicial = $parametros['dataInicial'];
         $dataFim = $parametros['dataFim'];

         $sql = "  SELECT HIST.COD_PRODUTO as \"COD.PRODUTO\",
                          HIST.DSC_GRADE as \"GRADE\",
                          P.DSC_PRODUTO \"PRODUTO\",
                          CASE WHEN HIST.QTD >= 0 THEN 'ENTRADA'
                               WHEN HIST.QTD < 0 THEN 'SAIDA'
                          END as \"TIPO\",
                          HIST.QTD as \"QTD.\",
                          DEP.DSC_DEPOSITO_ENDERECO as \"ENDERECO\",
                          TO_CHAR(HIST.DTH_MOVIMENTACAO,'DD/MM/YYYY HH24:MI:SS')as \"DTH.MOVIMENTACAO\",
                          PES.NOM_PESSOA as \"PESSOA\",
                          HIST.OBSERVACAO as \"OBSERVACAO\",
                          PA.UMA as \"PALETE\",
                          U.DSC_UNITIZADOR as \"UNITIZADOR\",
                          max(N.NUM_NORMA) AS \"NORMA\",
                          HIST.COD_OS as \"OS\",
                          OS.COD_RECEBIMENTO as \"RECEBIMENTO\"
                     FROM HISTORICO_ESTOQUE HIST
               INNER JOIN PRODUTO P ON (HIST.COD_PRODUTO = P.COD_PRODUTO AND HIST.DSC_GRADE = P.DSC_GRADE)
               INNER JOIN DEPOSITO_ENDERECO DEP ON HIST.COD_DEPOSITO_ENDERECO = DEP.COD_DEPOSITO_ENDERECO
                LEFT JOIN PESSOA PES ON HIST.COD_PESSOA = PES.COD_PESSOA
                LEFT JOIN ORDEM_SERVICO OS ON HIST.COD_OS = OS.COD_OS
                LEFT JOIN PALETE PA ON OS.COD_ENDERECAMENTO = PA.UMA
                LEFT JOIN PALETE_PRODUTO PPROD on PPROD.UMA = PA.UMA
                LEFT JOIN NORMA_PALETIZACAO N ON N.COD_NORMA_PALETIZACAO = PPROD.COD_NORMA_PALETIZACAO
                LEFT JOIN UNITIZADOR U ON U.COD_UNITIZADOR = PA.COD_UNITIZADOR
                    WHERE ((HIST.DTH_MOVIMENTACAO >= TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI'))
                       AND (HIST.DTH_MOVIMENTACAO <= TO_DATE('$dataFim 00:00', 'DD-MM-YYYY HH24:MI')))
                 GROUP BY
                       HIST.COD_PRODUTO ,
                          HIST.DSC_GRADE ,
                          P.DSC_PRODUTO ,

                          HIST.QTD ,
                          DEP.DSC_DEPOSITO_ENDERECO ,
                          HIST.DTH_MOVIMENTACAO,
                          PES.NOM_PESSOA ,
                          HIST.OBSERVACAO ,
                          PA.UMA ,
                          U.DSC_UNITIZADOR ,
                         N.NUM_NORMA,
                          HIST.COD_OS ,
                          OS.COD_RECEBIMENTO
                 ORDER BY HIST.DTH_MOVIMENTACAO, HIST.COD_PRODUTO, HIST.DSC_GRADE, HIST.QTD";

         $resultado = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);

         return $resultado;

     }
}
