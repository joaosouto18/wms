<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\Conferencia as ConferenciaEntity;

/**
 * Conferencia
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ConferenciaRepository extends EntityRepository
{

    public function getLastOsConferencia ($idRecebimento, $idProduto, $grade)
    {
        $query = "
					SELECT DTH_FINAL_ATIVIDADE, COD_OS FROM (
					SELECT CASE WHEN OS.DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
								ELSE OS.DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
								  RC.COD_OS
					  FROM RECEBIMENTO_CONFERENCIA RC
					  LEFT JOIN ORDEM_SERVICO OS ON RC.COD_OS = OS.COD_OS
					WHERE RC.COD_RECEBIMENTO = $idRecebimento  
					  AND RC.COD_PRODUTO = $idProduto
					  AND RC.DSC_GRADE = '$grade') ORDER BY DTH_FINAL_ATIVIDADE DESC
		";
        $result = $this->getEntityManager()->getConnection()->query($query)-> fetchAll(\PDO::FETCH_ASSOC);

        if ($result == NULL) {
            return 0;
        } else {
            return $result[0]['COD_OS'];
        }
    }

    public function getLastOsRecebimentoEmbalagem ($idRecebimento, $idProduto, $grade)
    {
        $query = "
            SELECT COD_OS
              FROM (SELECT DISTINCT
                           CASE WHEN OS.DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
                                                ELSE OS.DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
                           OS.COD_OS
                      FROM ORDEM_SERVICO OS
                     INNER JOIN RECEBIMENTO_EMBALAGEM RE ON RE.COD_OS = OS.COD_OS
                     INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                     WHERE OS.COD_RECEBIMENTO = '$idRecebimento'
                       AND COD_PRODUTO = '$idProduto'
                       AND DSC_GRADE = '$grade') QTD
             ORDER BY DTH_FINAL_ATIVIDADE DESC";
        $result = $this->getEntityManager()->getConnection()->query($query)-> fetchAll(\PDO::FETCH_ASSOC);

        if ($result == NULL) {
            return 0;
        } else {
            return $result[0]['COD_OS'];
        }
    }

    public function getLastOsRecebimentoVolume ($idRecebimento, $idProduto, $grade)
    {
        $query = "
            SELECT COD_OS
              FROM (SELECT DISTINCT
                           CASE WHEN OS.DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
                                                ELSE OS.DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
                           OS.COD_OS
                      FROM ORDEM_SERVICO OS
                     INNER JOIN RECEBIMENTO_VOLUME RV ON RV.COD_OS = OS.COD_OS
                     INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                     WHERE OS.COD_RECEBIMENTO = '$idRecebimento'
                       AND COD_PRODUTO = '$idProduto'
                       AND DSC_GRADE = '$grade') QTD
             ORDER BY DTH_FINAL_ATIVIDADE DESC";
        $result = $this->getEntityManager()->getConnection()->query($query)-> fetchAll(\PDO::FETCH_ASSOC);

        if ($result == NULL) {
            return 0;
        } else {
            return $result[0]['COD_OS'];
        }
    }

    public function getOsConferida ($idRecebimento, $idProduto, $grade)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("os.id")
            ->from("wms:Recebimento\Conferencia","c")
            ->innerJoin("c.ordemServico" , "os")
            ->where("c.recebimento = $idRecebimento")
            ->andWhere("c.grade = '$grade'")
            ->andWhere("c.codProduto = $idProduto")
            ->andWhere("(c.qtdDivergencia = 0 OR (c.qtdDivergencia != 0 AND NOT(c.notaFiscal IS NULL)))");
        $conferencia =  $source->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        if (count($conferencia) <= 0) {
            return 0;
        } else{
            return $conferencia[0]['id'];
        }
    }

    public function getQtdByRecebimentoVolumeAndNorma ($idOs, $codProduto, $grade){
        $SQL = "SELECT MIN (QTD) as QTD, COD_NORMA_PALETIZACAO, NUM_NORMA, COD_UNITIZADOR, SUM(NUM_PESO) as PESO
                  FROM (SELECT SUM(QTD_CONFERIDA) as QTD, RV.COD_PRODUTO_VOLUME, RV.COD_NORMA_PALETIZACAO, NP.NUM_NORMA, NP.COD_UNITIZADOR, SUM(RV.NUM_PESO) as NUM_PESO
                          FROM RECEBIMENTO_VOLUME RV
                         INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                         INNER JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = RV.COD_NORMA_PALETIZACAO
                         WHERE COD_OS = '$idOs'
                           AND PV.COD_PRODUTO = '$codProduto'
                           AND PV.DSC_GRADE = '$grade'
                         GROUP BY RV.COD_PRODUTO_VOLUME, RV.COD_NORMA_PALETIZACAO, NP.NUM_NORMA, COD_UNITIZADOR)
                 GROUP BY COD_NORMA_PALETIZACAO, NUM_NORMA, COD_UNITIZADOR, NUM_PESO";
        return $this->getEntityManager()->getConnection()->query($SQL)-> fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQtdByRecebimentoEmbalagemAndNorma ($idOs, $codProduto, $grade){
        $SQL = "SELECT SUM(RE.QTD_CONFERIDA * RE.QTD_EMBALAGEM) as QTD, RE.COD_NORMA_PALETIZACAO, (NP.NUM_NORMA * PE.QTD_EMBALAGEM) as NUM_NORMA, NP.COD_UNITIZADOR, SUM(RE.NUM_PESO) as PESO
                  FROM RECEBIMENTO_EMBALAGEM RE
                 INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                 INNER JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = RE.COD_NORMA_PALETIZACAO 
                 WHERE COD_OS = '$idOs'
                   AND PE.COD_PRODUTO = '$codProduto'
                   AND PE.DSC_GRADE = '$grade'
                 GROUP BY RE.COD_NORMA_PALETIZACAO, (NP.NUM_NORMA * PE.QTD_EMBALAGEM) , NP.COD_UNITIZADOR";
        return $this->getEntityManager()->getConnection()->query($SQL)-> fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQtdByRecebimento ($idRecebimento, $idProduto, $grade)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("r.qtd, r.codNormaPaletizacao as idNormaPaletizacao, np.numNorma, un.id as idUnitizador")
            ->from("wms:Recebimento\VQtdRecebimento", "r")
            ->leftjoin("wms:Produto\NormaPaletizacao", "np","WITH","np.id = r.codNormaPaletizacao")
            ->leftJoin('np.unitizador','un')
            ->where("r.codProduto = '$idProduto'")
            ->andWhere("r.grade = '$grade'")
            ->andWhere("r.codRecebimento = $idRecebimento");
        $result = $query->getQuery()->getArrayResult();

        if (count($result) > 0) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     *
     * @param int $idOrdemServico
     * @return array Result set
     */
    public function getProdutoDivergencia($idOrdemServico)
    {

        $SQL = " SELECT RC.COD_RECEBIMENTO_CONFERENCIA,
                        P.COD_PRODUTO,
                        P.DSC_GRADE,
                        P.DSC_PRODUTO,
                        RC.QTD_CONFERIDA,
                        RC.QTD_AVARIA,
                        RC.QTD_DIVERGENCIA,
                        PESONF.PESO as PESO_NF,
                        P.DSC_REFERENCIA,
                        NVL(V.NUM_PESO,0) as PES_RECEBIDO,
                        P.TOLERANCIA_NOMINAL,
                        P.IND_POSSUI_PESO_VARIAVEL
                   FROM RECEBIMENTO_CONFERENCIA RC
                  INNER JOIN PRODUTO P ON P.COD_PRODUTO = RC.COD_PRODUTO AND P.DSC_GRADE = RC.DSC_GRADE
                   LEFT JOIN (SELECT * FROM V_QTD_RECEBIMENTO V WHERE COD_OS = $idOrdemServico) V
                          ON V.COD_PRODUTO = RC.COD_PRODUTO
                         AND V.DSC_GRADE = RC.DSC_GRADE
                         AND V.COD_OS = RC.COD_OS
                         AND V.COD_RECEBIMENTO = RC.COD_RECEBIMENTO
                  INNER JOIN (SELECT SUM(NUM_PESO) PESO,
                                    NFI.COD_PRODUTO,
                                    NFI.DSC_GRADE,
                                    NF.COD_RECEBIMENTO
                               FROM NOTA_FISCAL NF
                               LEFT JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL
                               GROUP BY NFI.COD_PRODUTO,
                                        NFI.DSC_GRADE,
                                        NF.COD_RECEBIMENTO) PESONF ON PESONF.COD_RECEBIMENTO = RC.COD_RECEBIMENTO
                                                                  AND PESONF.COD_PRODUTO = RC.COD_PRODUTO
                                                                  AND PESONF.DSC_GRADE = RC.DSC_GRADE
                  WHERE RC.COD_OS = $idOrdemServico
                    AND (RC.QTD_DIVERGENCIA <> 0 OR RC.IND_DIVERGENCIA_PESO = 'S')";
        $result =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = array();
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");

        foreach ($result as $line) {
            $idProduto = $line['COD_PRODUTO'];
            $idRecebimentoConferencia = $line['COD_RECEBIMENTO_CONFERENCIA'];
            $grade = $line['DSC_GRADE'];
            $dscProduto = $line['DSC_PRODUTO'];
            $qtdConferida = $line['QTD_CONFERIDA'];
            $qtdAvaria = $line['QTD_AVARIA'];
            $qtdDivergencia = $line['QTD_DIVERGENCIA'];
            $pesoNf = $line['PESO_NF'];
            $pesoRecebimento = $line ['PES_RECEBIDO'];
            $toleranciaNominal = $line['TOLERANCIA_NOMINAL'];
            $referencia = $line['DSC_REFERENCIA'];
            $possuiPesoVariavel = $line['IND_POSSUI_PESO_VARIAVEL'];

            if ($qtdDivergencia == 0) {
                $qtdConferida = $pesoRecebimento . " Kg";
                if ($pesoRecebimento > $pesoNf - $toleranciaNominal) {
                    $qtdDivergencia = $pesoRecebimento - $pesoNf - $toleranciaNominal;
                } else {
                    $qtdDivergencia = $pesoRecebimento - $pesoNf + $toleranciaNominal;
                }
                $qtdDivergencia = $qtdDivergencia . " Kg";
            }
            if ($qtdConferida > 0) {
                $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($idProduto, $grade, $qtdConferida);
                if (is_array($vetSeparar)) {
                    $qtdConferida = implode(' + ', $vetSeparar);
                }
            }
            if ($qtdDivergencia > 0) {
                $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($idProduto, $grade, $qtdDivergencia);
                if (is_array($vetSeparar)) {
                    $qtdDivergencia = implode(' + ', $vetSeparar);
                }
            }
            $resultArr[] = array(
                'id'=>$idRecebimentoConferencia,
                'idProduto' =>$idProduto,
                'grade' => $grade,
                'dscProduto' => $dscProduto,
                'qtdConferida' => $qtdConferida,
                'qtdAvaria' => $qtdAvaria,
                'qtdDivergencia' => $qtdDivergencia,
                'referencia' => $referencia,
                'possui_peso_variavel' => $possuiPesoVariavel
            );
        }
        return $resultArr;
    }

    public function getSumPesoTotalRecebimentoProduto($recebimento,$codProduto,$grade,$ordemServicoEntity)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('NVL(SUM(re.numPeso),SUM(rv.numPeso)) numPeso, NVL(SUM(re.qtdConferida), SUM(rv.qtdConferida)) qtdConferida, p.id produto, p.grade')
            ->from('wms:Produto', 'p')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.codProduto = p.id AND pe.grade = p.grade')
            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'pv.codProduto = p.id AND pv.grade = p.grade')
            ->leftJoin('wms:Recebimento\Embalagem', 're', 'WITH', "re.embalagem = pe.id")
            ->leftJoin('wms:Recebimento\Volume', 'rv', 'WITH', 'rv.volume = pv.id')
            ->leftJoin('wms:Recebimento', 'r', 'WITH', 'r.id = re.recebimento OR r.id = rv.recebimento')
            ->andWhere("r.id = $recebimento")
            ->andWhere("re.ordemServico = :ordens OR rv.ordemServico = :ordens")
            ->setParameter('ordens', $ordemServicoEntity->getId())
            ->groupBy("p.id, p.grade");

        if (!is_null($codProduto) && !is_null($grade)) {
            $sql->andWhere("p.id = '$codProduto'")
                ->andWhere("p.grade = '$grade'");
        }

        return $sql->getQuery()->getResult();
    }

    public function getProdutosByRecebimento($idRecebimento)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('1010101010 codigoBarras, p.id codProduto, p.grade, SUM(rc.qtdConferida) + SUM(rc.qtdDivergencia * -1) quantidade, SUM(rc.qtdDivergencia * -1) qtdDivergencia, rc.dataValidade, rc.dataConferencia')
            ->from('wms:Recebimento\Conferencia', 'rc')
            ->innerJoin('rc.recebimento', 'r')
            ->innerJoin('wms:Produto','p', 'WITH', 'p.id = rc.codProduto and p.grade = rc.grade')
            ->where("r.id = $idRecebimento")
            ->andWhere('rc.qtdDivergencia = 0 OR rc.notaFiscal IS NOT NULL')
            ->groupBy('p.id, p.grade, rc.dataValidade, rc.dataConferencia');

        return $sql->getQuery()->getResult();
    }

}
