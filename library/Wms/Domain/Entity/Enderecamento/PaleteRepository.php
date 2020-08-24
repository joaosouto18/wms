<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    DoctrineExtensions\Versionable\Exception,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\Recebimento;
use Wms\Domain\Entity\Armazenagem\UnitizadorRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\EmbalagemRepository;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoque;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamento;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamentoRepository;
use Wms\Math;

class PaleteRepository extends EntityRepository {

    public function getQtdProdutosByRecebimento($params) {
        extract($params);

        $filter = false;

        $queryWhere = " WHERE ";

        if (isset($dataInicial1) && (!empty($dataInicial1))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB >= TO_DATE('$dataInicial1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataInicial2) && (!empty($dataInicial2))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB <= TO_DATE('$dataInicial2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal1) && (!empty($dataFinal1))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB >= TO_DATE('$dataFinal1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal2) && (!empty($dataFinal2))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB <= TO_DATE('$dataFinal2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($status) && (!empty($status))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            if ($status != 536) {
                $queryWhere = $queryWhere . " R.COD_STATUS = $status";
            } else {
                $queryWhere .= " R.COD_STATUS = 457 ";
            }
            $filter = true;
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.COD_RECEBIMENTO = $idRecebimento";
            $filter = true;
        }

        if (isset($uma) && (!empty($uma))) {
            if ($filter == true) {
                $queryWhere = $queryWhere . " AND ";
            }
            $queryWhere = $queryWhere . " R.COD_RECEBIMENTO IN (SELECT COD_RECEBIMENTO FROM PALETE WHERE UMA = $uma)";
            $filter = true;
        }

        $recebimentosSQL = " SELECT COD_RECEBIMENTO FROM RECEBIMENTO R";
        if ($filter == true) {
            $recebimentosSQL .= $queryWhere;
            $recebimentosSQL .= " AND ROWNUM <= 1000 ORDER BY COD_RECEBIMENTO DESC";
        }
        $resultRecebimentos = $this->getEntityManager()->getConnection()->query($recebimentosSQL)->fetchAll(\PDO::FETCH_ASSOC);

        $recebimentos = "";
        foreach ($resultRecebimentos as $row) {
            $separador = ",";
            if ($recebimentos == "") {
                $separador = "";
            }
            $recebimentos .= $separador . $row['COD_RECEBIMENTO'];
        }

        $whereCodRecebimento = "";
        if ($filter == true) {
            if (count($resultRecebimentos) > 0) {
                $whereCodRecebimento = " WHERE R.COD_RECEBIMENTO IN (" . $recebimentos . ")";
            } else {
                $whereCodRecebimento = " WHERE R.COD_RECEBIMENTO IN (0)";
            }
        }

        $stsPaleteEnderecado = Palete::STATUS_ENDERECADO;
        $recebimentoFinalizado = Recebimento::STATUS_FINALIZADO;

        $query = "
        SELECT R.COD_RECEBIMENTO,
               R.DTH_INICIO_RECEB,
               R.DTH_FINAL_RECEB,
               '' as EMISSORES,
               CASE WHEN R.COD_STATUS = $recebimentoFinalizado AND QTD_TOTAL.QTD_TOTAL = NVL(QTD_END.QTD,0) THEN 'ENDEREÇADO' ELSE
               S.DSC_SIGLA END as STATUS,
               QTD_TOTAL.QTD_TOTAL as QTD_RECEBIDA,
               NVL(QTD_END.QTD,0) As QTD_ENDERECADA,
               ROUND(NVL(QTD_END.QTD,0)/NVL(QTD_TOTAL.QTD_TOTAL,1) * 100,2) as PERCENTUAL
          FROM RECEBIMENTO R
          INNER JOIN DEPOSITO D ON D.COD_DEPOSITO = R.COD_DEPOSITO
          LEFT JOIN SIGLA S ON S.COD_SIGLA = R.COD_STATUS
          LEFT JOIN (SELECT SUM(QTD) as QTD_TOTAL, COD_RECEBIMENTO 
                       FROM (SELECT SUM (QTD) as QTD, COD_RECEBIMENTO
                               FROM V_QTD_RECEBIMENTO R
                               $whereCodRecebimento
                              GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE)
                      GROUP BY COD_RECEBIMENTO) QTD_TOTAL ON QTD_TOTAL.COD_RECEBIMENTO = R.COD_RECEBIMENTO AND QTD_TOTAL.QTD_TOTAL > 0
          LEFT JOIN (SELECT SUM(QTD) as QTD, COD_RECEBIMENTO
                     FROM (SELECT DISTINCT (SUM(PP.QTD) / NVL(PV.QTD_VOLUMES, 1)) QTD, PP.COD_PRODUTO, PP.DSC_GRADE, R.COD_RECEBIMENTO
                           FROM PALETE_PRODUTO PP
                           INNER JOIN PALETE R ON R.UMA = PP.UMA
                           LEFT JOIN (SELECT COUNT(DISTINCT COD_PRODUTO_VOLUME) QTD_VOLUMES, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV 
                           GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = PP.COD_PRODUTO AND PV.DSC_GRADE = PP.DSC_GRADE
                           $whereCodRecebimento AND R.COD_STATUS = $stsPaleteEnderecado
                           GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, R.COD_RECEBIMENTO, PV.QTD_VOLUMES)
                     GROUP BY COD_RECEBIMENTO) QTD_END ON QTD_END.COD_RECEBIMENTO = R.COD_RECEBIMENTO";
        $query = $query . $whereCodRecebimento . " AND NVL(D.IND_USA_ENDERECAMENTO, 'S') = 'S' ";

        if (isset($status) && (!empty($status))) {
            if ($status == $stsPaleteEnderecado) {
                $query .= " AND QTD_TOTAL.QTD_TOTAL = NVL(QTD_END.QTD,0) ";
            } else {
                $query .= " AND QTD_TOTAL.QTD_TOTAL != NVL(QTD_END.QTD,0) ";
            }
        }

        $query = $query . " ORDER BY R.COD_RECEBIMENTO";

        $queryFornecedores = "
                      SELECT R.COD_RECEBIMENTO,
                            LISTAGG(P.NOM_PESSOA, ', ')  WITHIN GROUP (ORDER BY P.NOM_PESSOA) EMISSORES
                       FROM (SELECT DISTINCT COD_RECEBIMENTO, COD_EMISSOR FROM NOTA_FISCAL) R
                       LEFT JOIN PESSOA P ON R.COD_EMISSOR = P.COD_PESSOA
                       $whereCodRecebimento
                      GROUP BY R.COD_RECEBIMENTO
        ";

        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $resultFornecedores = $this->getEntityManager()->getConnection()->query($queryFornecedores)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $key => $recebimento) {
            foreach ($resultFornecedores as $fornecedor) {
                if ($recebimento['COD_RECEBIMENTO'] == $fornecedor['COD_RECEBIMENTO']) {
                    $result[$key]['EMISSORES'] = $fornecedor['EMISSORES'];
                    break;
                }
            }
        }

        return $result;
    }

    public function getPaletes($idRecebimento, $idProduto, $grade, $trowException = true, $tipoEnderecamento = 'A') {
        $this->gerarPaletes($idRecebimento, $idProduto, $grade, $trowException, $tipoEnderecamento);
        $paletes = $this->getPaletesAndVolumes($idRecebimento, $idProduto, $grade);

        return $paletes;
    }

    public function getPaletesByUnitizador($idRecebimento = null, $idProduto = null, $grade = null, $detalhePalete = false) {
        if ($detalhePalete == true) {
            $SQL = " SELECT DISTINCT MAX(P.UMA) as UMA,";
        } else {
            $SQL = " SELECT LISTAGG(P.UMA, ',') WITHIN GROUP (ORDER BY PRODUTO.COD_PRODUTO, PRODUTO.DSC_GRADE) as UMA,";
        }

        $SQL .= " U.DSC_UNITIZADOR as UNITIZADOR,
                        SUM(QTD.QTD) as QTD,
                        P.IND_IMPRESSO,
                        PRODUTO.COD_PRODUTO,
                        PRODUTO.DSC_GRADE,
                        PRODUTO.DSC_PRODUTO,
                        R.COD_RECEBIMENTO,
                        S.COD_SIGLA as COD_SIGLA,
                        NVL(QTD_VOL.QTD,1) as QTD_VOL_TOTAL,
                        NVL(QTD_VOL_CONFERIDO.QTD,1) as QTD_VOL_CONFERIDO";

        if ($detalhePalete == true) {
            $SQL .= ",DE.DSC_DEPOSITO_ENDERECO as ENDERECO";
        }

        $SQL .= " FROM PALETE P
                   LEFT JOIN UNITIZADOR U ON P.COD_UNITIZADOR = U.COD_UNITIZADOR
                   LEFT JOIN SIGLA S ON P.COD_STATUS = S.COD_SIGLA";

        if ($detalhePalete == true) {
            $SQL .= " LEFT JOIN DEPOSITO_ENDERECO DE ON P.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO";
        }

        $SQL .= " LEFT JOIN RECEBIMENTO R ON R.COD_RECEBIMENTO = P.COD_RECEBIMENTO
                   INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                   INNER JOIN PRODUTO ON PRODUTO.COD_PRODUTO = PP.COD_PRODUTO AND PP.DSC_GRADE = PRODUTO.DSC_GRADE
                   INNER JOIN (SELECT MIN(PP.QTD) as QTD, UMA FROM PALETE_PRODUTO PP GROUP BY UMA) QTD ON QTD.UMA = P.UMA
                    LEFT JOIN (SELECT COUNT(COD_PRODUTO_VOLUME) QTD, COD_NORMA_PALETIZACAO
                                 FROM PRODUTO_VOLUME
                                GROUP BY COD_NORMA_PALETIZACAO) QTD_VOL ON QTD_VOL.COD_NORMA_PALETIZACAO = PP.COD_NORMA_PALETIZACAO
                   INNER JOIN (SELECT COUNT(COD_PALETE_PRODUTO) QTD, UMA
                                 FROM PALETE_PRODUTO
                                GROUP BY UMA) QTD_VOL_CONFERIDO ON QTD_VOL_CONFERIDO.UMA = P.UMA
                   WHERE 1 = 1 ";
        if (($idProduto != NULL) && ($idProduto != "")) {
            $SQL .= " AND PRODUTO.COD_PRODUTO = '$idProduto'";
        }
        if (($grade != NULL) && ($grade != "")) {
            $SQL .= " AND PRODUTO.DSC_GRADE = '$grade'";
        }
        if (($idRecebimento != null) && $idRecebimento != "") {
            $SQL .= " AND P.COD_RECEBIMENTO = '$idRecebimento'";
        }
        if ($detalhePalete == false) {
            $SQL .= " GROUP BY U.DSC_UNITIZADOR, P.IND_IMPRESSO, S.COD_SIGLA,
                    PRODUTO.COD_PRODUTO, PRODUTO.DSC_GRADE, PRODUTO.DSC_PRODUTO, R.COD_RECEBIMENTO,
                    QTD_VOL.QTD, QTD_VOL_CONFERIDO.QTD";
        } else {
            $SQL .= " GROUP BY P.UMA, DE.DSC_DEPOSITO_ENDERECO, QTD.QTD, U.DSC_UNITIZADOR,
                    P.IND_IMPRESSO, S.COD_SIGLA, PRODUTO.COD_PRODUTO, PRODUTO.DSC_GRADE, PRODUTO.DSC_PRODUTO,
                    R.COD_RECEBIMENTO, QTD_VOL.QTD, QTD_VOL_CONFERIDO.QTD";
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getQtdEnderecadaByNormaPaletizacao($idRecebimento, $idProduto, $grade, $tipo = "V") {

        if ($tipo == "V") {
            $norma = " PP.COD_NORMA_PALETIZACAO ";
            $groupNorma = ", PP.COD_NORMA_PALETIZACAO ";
        } else {
            $norma = " 0 ";
            $groupNorma = " ";
        }

        $SQL = "SELECT SUM(QTD.QTD) as QTD, QTD.COD_NORMA_PALETIZACAO, SUM(QTD.PESO) AS PESO, NVL(DSC_LOTE, '') as LOTE
                  FROM (SELECT P.UMA, PP.QTD, $norma as COD_NORMA_PALETIZACAO, SUM(P.PESO) AS PESO, PP.DSC_LOTE
                          FROM PALETE P
                     INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                         WHERE PP.COD_PRODUTO = '$idProduto' 
                           AND PP.DSC_GRADE = '$grade'
                           AND P.COD_RECEBIMENTO = '$idRecebimento'
                           AND (P.IND_IMPRESSO <> 'N' OR P.COD_STATUS <> " . Palete::STATUS_EM_RECEBIMENTO . ")
                     GROUP BY
                        P.UMA, PP.QTD, PP.DSC_LOTE $groupNorma
                           ) QTD
                 GROUP BY QTD.COD_NORMA_PALETIZACAO, NVL(DSC_LOTE, '')";

        $result = [];
        foreach ($this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC) as $item) {
            $result["$item[COD_NORMA_PALETIZACAO]-+-$item[LOTE]"] = $item;
        }

        return $result;
    }

    public function getEmbalagensByOsAndNorma($codOs, $codProduto, $grade, $normaPaletizacao, $codRecebimento) {
        $SQL = "
        SELECT DISTINCT
               NULL as COD_PRODUTO_VOLUME,
               MAX(RE.COD_PRODUTO_EMBALAGEM) COD_PRODUTO_EMBALAGEM
          FROM RECEBIMENTO_EMBALAGEM RE
         INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
          WHERE RE.COD_RECEBIMENTO = '$codRecebimento'
        AND RE.COD_OS = '$codOs'
        AND PE.COD_PRODUTO = '$codProduto'
        AND PE.DSC_GRADE = '$grade'";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getVolumesByOsAndNorma($codOs, $codProduto, $grade, $normaPaletizacao, $codRecebimento) {
        $SQL = "
        SELECT DISTINCT
               RV.COD_PRODUTO_VOLUME,
               NULL as COD_PRODUTO_EMBALAGEM
          FROM RECEBIMENTO_VOLUME RV
         INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
          WHERE RV.COD_RECEBIMENTO = '$codRecebimento'
            AND RV.COD_OS = '$codOs'
            AND RV.COD_NORMA_PALETIZACAO = '$normaPaletizacao'
            AND PV.COD_PRODUTO = '$codProduto'
            AND PV.DSC_GRADE = '$grade'";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getQtdByProdutoAndStatus($idRecebimento, $idProduto, $grade, $codStatus) {
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("SUM(pp.qtd) as menorQtd")
                ->from("wms:Enderecamento\Palete", "p")
                ->leftJoin("wms:Enderecamento\PaleteProduto", "pp", 'WITH', 'pp.uma = p.id')
                ->leftJoin("wms:Produto\Embalagem", "pe", 'WITH', 'pp.codProdutoEmbalagem = pe.id')
                ->leftJoin("wms:Produto\Volume", "pv", 'WITH', 'pp.codProdutoVolume = pv.id')
                ->where("(pv.codProduto = '$idProduto' AND pv.grade = '$grade') OR (pe.codProduto = '$idProduto' AND pe.grade = '$grade')")
                ->andWhere("p.recebimento = $idRecebimento")
                ->andWhere("p.codStatus = " . $codStatus)
                ->groupBy('pp.codNormaPaletizacao, pp.codProdutoEmbalagem, pp.codProdutoVolume ')
                ->orderBy("menorQtd")
                ->distinct(true);
        $result = $query->getQuery()->getArrayResult();

        $qtd = 0;

        $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));
        if (count($produtoEn->getVolumes()) == 0) {
            foreach ($result as $line) {
                $qtd = $qtd + $line['menorQtd'];
            }
        } else {
            if (count($result) > 0) {
                if ($result[0]['menorQtd'] != NULL) {
                    $qtd = $result[0]['menorQtd'];
                }
            }
        }

        return $qtd;
    }

    public function getPaletesAndVolumes($idRecebimento = null, $idProduto = null, $grade = null, $statusPalete = null, $statusRecebimento = null, $dtInicioRecebimento1 = null, $dtInicioRecebimento2 = null, $dtFinalRecebimento1 = null, $dtFinalRecebimento2 = null, $uma = null, $ordem = null) {
        $SQL = " SELECT DISTINCT
                        P.UMA,
                        U.DSC_UNITIZADOR as UNITIZADOR,
                        CASE WHEN PRODUTO.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO || ' Kg'  
                             ELSE TO_CHAR(QTD.QTD)
                        END as QTD,
                        S.DSC_SIGLA as STATUS,
                        DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                        P.IND_IMPRESSO,
                        PRODUTO.COD_PRODUTO,
                        PRODUTO.DSC_GRADE,
                        PRODUTO.DSC_PRODUTO,
                        R.COD_RECEBIMENTO,
                        S.COD_SIGLA as COD_SIGLA,
                        PROD.VOLUMES,
                        NVL(QTD_VOL.QTD,1) as QTD_VOL_TOTAL,
                        NVL(QTD_VOL_CONFERIDO.QTD,1) as QTD_VOL_CONFERIDO,
                        PP.DTH_VALIDADE,
                        PPL.DSC_LOTE AS LOTE
                   FROM PALETE P
                   LEFT JOIN UNITIZADOR U ON P.COD_UNITIZADOR = U.COD_UNITIZADOR
                   LEFT JOIN SIGLA S ON P.COD_STATUS = S.COD_SIGLA
                   LEFT JOIN DEPOSITO_ENDERECO DE ON P.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                   LEFT JOIN RECEBIMENTO R ON R.COD_RECEBIMENTO = P.COD_RECEBIMENTO                   
                   LEFT JOIN (SELECT LISTAGG(DSC_LOTE, ', ') WITHIN GROUP (ORDER BY UMA) DSC_LOTE, UMA                   
                   FROM PALETE_PRODUTO GROUP BY UMA) PPL ON PPL.UMA = P.UMA                   
                   INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                   INNER JOIN PRODUTO ON PRODUTO.COD_PRODUTO = PP.COD_PRODUTO AND PP.DSC_GRADE = PRODUTO.DSC_GRADE
                   INNER JOIN (SELECT (SUM(PP.QTD) / COUNT(DISTINCT NVL(COD_PRODUTO_VOLUME,1))) as QTD, UMA FROM PALETE_PRODUTO PP GROUP BY UMA) QTD ON QTD.UMA = P.UMA
                    LEFT JOIN (SELECT COUNT(COD_PRODUTO_VOLUME) QTD, COD_NORMA_PALETIZACAO
                                 FROM PRODUTO_VOLUME
                                GROUP BY COD_NORMA_PALETIZACAO) QTD_VOL ON QTD_VOL.COD_NORMA_PALETIZACAO = PP.COD_NORMA_PALETIZACAO
                   INNER JOIN (SELECT COUNT(COD_PALETE_PRODUTO) QTD, UMA
                                 FROM PALETE_PRODUTO
                                GROUP BY UMA) QTD_VOL_CONFERIDO ON QTD_VOL_CONFERIDO.UMA = P.UMA
                   INNER JOIN (SELECT PP.UMA,
                                      LISTAGG(NVL(PV.DSC_VOLUME,PE.DSC_EMBALAGEM), ', ') WITHIN GROUP (ORDER BY PP.UMA) VOLUMES
                                 FROM PALETE_PRODUTO PP
                                 LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = PP.COD_PRODUTO_VOLUME
                                 LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = PP.COD_PRODUTO_EMBALAGEM
                                GROUP BY PP.UMA) PROD ON PROD.UMA = P.UMA
                   WHERE 1 = 1 ";
        if (($idProduto != NULL) && ($idProduto != "")) {
            $SQL .= " AND PRODUTO.COD_PRODUTO = '$idProduto'";
        }
        if (($grade != NULL) && ($grade != "")) {
            $SQL .= " AND PRODUTO.DSC_GRADE = '$grade'";
        }
        if (($idRecebimento != null) && $idRecebimento != "") {
            $SQL .= " AND P.COD_RECEBIMENTO = '$idRecebimento'";
        }
        if (($uma != null) && ($uma != "")) {
            $SQL .= " AND P.UMA = '$uma'";
        }
        if (($statusPalete != Null) && ($statusPalete != "")) {
            $SQL .= " AND P.COD_STATUS = '$statusPalete'";
        }
        if (($statusRecebimento != NUll) && ($statusRecebimento != "")) {
            $SQL .= " AND R.COD_STATUS = '$statusPalete'";
        }
        if (($dtInicioRecebimento1 != NULL) && ($dtInicioRecebimento1 != "")) {
            $SQL .= " AND R.DTH_INICIO_RECEB >= TO_DATE('$dtInicioRecebimento1 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (($dtInicioRecebimento2 != NULL) && ($dtInicioRecebimento2 != "")) {
            $SQL .= " AND R.DTH_INICIO_RECEB <= TO_DATE('$dtInicioRecebimento2 23:59','DD-MM-YYYY HH24:MI')";
        }
        if (($dtFinalRecebimento1 != NULL) && ($dtFinalRecebimento1 != "")) {
            $SQL .= " AND R.DTH_FINAL_RECEB >= TO_DATE('$dtFinalRecebimento1 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (($dtFinalRecebimento2 != NULL) && ($dtFinalRecebimento2 != "")) {
            $SQL .= " AND R.DTH_FINAL_RECEB <= TO_DATE('$dtFinalRecebimento2 23:59','DD-MM-YYYY HH24:MI')";
        }
        switch ($ordem) {
            case 1:
                $SQL .= "   ORDER BY P.UMA, S.DSC_SIGLA";
                break;
            case 2:
                $SQL .= "   ORDER BY DE.DSC_DEPOSITO_ENDERECO, P.UMA, S.DSC_SIGLA";
                break;
            default:
                $SQL .= "   ORDER BY PROD.VOLUMES, P.UMA, S.DSC_SIGLA";
                break;
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($result) && is_array($result)) {
            /** @var EmbalagemRepository $embalagemRepo */
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            foreach ($result as $key => $value) {
                if ($value['QTD'] > 0) {
                    $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD']);
                    if (is_array($vetSeparar)) {
                        $result[$key]['QTD'] = implode('<br />', $vetSeparar);
                    }else{
                        $result[$key]['QTD'] = $value['QTD'];
                    }
                }
            }
        }
        return $result;
    }

    public function deletaPaletesEmRecebimento($idRecebimento, $idProduto, $grade) {

        $reservaEstoqueRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoque");
        $reservaEstoqueEnderecamentoRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

        $statusRecebimento = Palete::STATUS_EM_RECEBIMENTO;
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("pa")
                ->from("wms:Enderecamento\Palete", "pa")
                ->leftJoin("wms:Enderecamento\PaleteProduto", "pp", 'WITH', 'pp.uma = pa.id')
                ->innerJoin("pa.recebimento", "r")
                ->innerJoin("pa.status", "s")
                ->where("r.id = '$idRecebimento'")
                ->andWhere("s.id = '$statusRecebimento'")
                ->andWhere("pa.impresso = 'N'")
                ->andWhere("(pp.codProduto = '$idProduto' AND pp.grade = '$grade')")
                ->distinct(true);
        $paletes = $query->getQuery()->getResult();
        foreach ($paletes as $key => $palete) {
            $reservaEstoqueEnderecamentoEn = $reservaEstoqueEnderecamentoRepo->findOneBy(array('palete' => $palete->getId()));

            if (count($reservaEstoqueEnderecamentoEn) > 0) {
                $reservaEstoqueEn = $reservaEstoqueRepo->findOneBy(array('id' => $reservaEstoqueEnderecamentoEn->getReservaEstoque()->getId()));
                $this->getEntityManager()->remove($reservaEstoqueEn);
                $this->getEntityManager()->remove($reservaEstoqueEnderecamentoEn);
            }
            $this->getEntityManager()->remove($palete);
        }
        $this->_em->flush();
    }

    /** EXEMPLO DE USO DA FUNÇÃO ENDERECAPICKING
      $paletesMock = array('116','117');
      $paleteRepo = $this->_em->getRepository('wms:Enderecamento\Palete');
      $paleteRepo->enderecaPicking($paletesMock);
     */
    public function enderecaPicking($paletes = array(), $completaPicking = false) {
        $Resultado = "";
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($paletes == NULL) {
            throw new \Exception("Nenhum Palete Selecionado");
        }

        foreach ($paletes as $palete) {
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete")->find($palete);

            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }

            $produtos = $paleteEn->getProdutos();
            if ($produtos) {
                $embalagem = $produtos[0]->getEmbalagemEn();
                $pickingEn = $embalagem->getEndereco();
                $codProduto = $produtos[0]->getCodProduto();
                $grade = $produtos[0]->getGrade();
                $capacidadePicking = $embalagem->getCapacidadePicking();
                $quantidadePalete = $produtos[0]->getQtd();

                if ($pickingEn == Null) {
                    throw new \Exception("Não existe endereço de picking para o produto " . $embalagem->getCodProduto() . " / " . $embalagem->getGrade());
                }

                $idVolume = null;
                $volumes = array();
                if ($produtos[0]->getCodProdutoVolume() != NULL) {
                    $idVolume = $produtos[0]->getCodProdutoVolume();
                    foreach ($produtos as $volume) {
                        $volumes[] = $volume->getCodProdutoVolume();
                    }
                }

                $saldoPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto, $grade, $pickingEn->getId(), $volumes);
                $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $pickingEn->getId(), "E");
                $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $pickingEn->getId(), "S");
                $saldoPickingVirtual = Math::adicionar(Math::adicionar($saldoPickingReal, $reservaEntradaPicking), $reservaSaidaPicking);

                if ($completaPicking) {
                    $quantidadeEnderecarPicking = PaleteProdutoRepository::getQuantidadeEnderecarPicking($capacidadePicking, $saldoPickingVirtual, $quantidadePalete);
                    if (is_string($quantidadeEnderecarPicking)) {
                        throw new \Exception($quantidadeEnderecarPicking);
                    }

                    $paleteProdutoEn = $this->getEntityManager()->getReference('wms:Enderecamento\PaleteProduto', $produtos[0]->getId());
                    $paleteProdutoEn->setQtd($quantidadeEnderecarPicking);
                    $this->getEntityManager()->persist($paleteProdutoEn);

                } else {
                    if (($saldoPickingVirtual + $quantidadePalete) > $capacidadePicking) {
                        $Resultado = "Quantidade nos paletes superior a capacidade do picking";
                    }
                }

                $this->alocaEnderecoPalete($paleteEn->getId(), $embalagem->getEndereco()->getId());
            }
            $this->getEntityManager()->flush();
        }
        return $Resultado;
    }

    /**
     * @param array $itens
     * @param $idRecebimento
     * @return bool
     * @throws Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    public function encherPicking(array $itens, $idRecebimento) {

        /** @var Recebimento $recebimentoEn */
        $recebimentoEn = $this->_em->find("wms:Recebimento", $idRecebimento);

        if ($recebimentoEn->getStatus()->getId() != Recebimento::STATUS_FINALIZADO) {
            throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
        }

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->_em->getRepository("wms:Produto");

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->_em->getRepository("wms:Enderecamento\Estoque");

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoque");

        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo = $this->_em->getRepository('wms:Recebimento\Conferencia');

        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');

        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
        $volumeRepo = $this->_em->getRepository('wms:Produto\Volume');

        /** @var UnitizadorRepository $unitizadorRepo */
        $unitizadorRepo = $this->_em->getRepository("wms:Armazenagem\Unitizador");

        $paletes = [];
        foreach ($itens as $prodGrade) {

            list($idProduto, $grade) = explode('-', $prodGrade);

            /** @var Produto $produtoEn */
            $produtoEn = $produtoRepo->find(['id' => $idProduto, "grade" => $grade]);

            if ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO) {

                list($pickingEn, $capacidadePicking) = $embalagemRepo->getCapacidadeAndPickingEmb($idProduto, $grade);

                $tipo = "E";
                $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($recebimentoEn->getId(), $idProduto, $grade, $tipo);
                $idOs = $conferenciaRepo->getLastOsRecebimentoEmbalagem($idRecebimento, $idProduto, $grade);
                $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoEmbalagemAndNorma($idOs, $idProduto, $grade);

            } else {

                list($pickingEn, $capacidadePicking) = $volumeRepo->getCapacidadeAndPickingVol($idProduto, $grade);

                $tipo = "V";
                $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($recebimentoEn->getId(), $idProduto, $grade, $tipo);
                $idOs = $conferenciaRepo->getLastOsRecebimentoVolume($idRecebimento, $idProduto, $grade);
                $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoVolumeAndNorma($idOs, $idProduto, $grade);

            }

            if (count($qtdRecebida) <= 0) {
                throw new Exception("O recebimento do produto $idProduto não possui unitizador ou ainda não foi conferido!");
            }

            $qtdDisponivelEnderecar = [];

            if (!empty($qtdEnderecada)) {
                foreach ($qtdEnderecada as $enderecado) {
                    foreach ($qtdRecebida as $key => $recebido) {
                        //if ($recebido['COD_NORMA_PALETIZACAO'] == $enderecado['COD_NORMA_PALETIZACAO'] && $recebido['LOTE'] === $enderecado['LOTE']) {
                        if ($recebido['COD_NORMA_PALETIZACAO'] == $enderecado['COD_NORMA_PALETIZACAO']) {
                            $qtdRecebida[$key]['QTD'] = $recebido['QTD'] - $enderecado['QTD'];
                            $qtdRecebida[$key]['PESO'] = $recebido['PESO'] - $enderecado['PESO'];
                            if ($qtdRecebida[$key]['QTD'] > 0 || $qtdRecebida[$key]['PESO'] > 0) {
                                //$qtdDisponivelEnderecar["$recebido[COD_NORMA_PALETIZACAO]-*-$recebido[LOTE]"] = [
                                $qtdDisponivelEnderecar[$recebido['COD_NORMA_PALETIZACAO']] = [
                                    'QTD' => $qtdRecebida[$key]['QTD'],
                                    'PESO' => $qtdRecebida[$key]['PESO'],
                                    'NUM_NORMA' => $qtdRecebida[$key]['NUM_NORMA'],
                                    'COD_NORMA_PALETIZACAO' => $qtdRecebida[$key]['COD_NORMA_PALETIZACAO'],
                                    'COD_UNITIZADOR' => $qtdRecebida[$key]['COD_UNITIZADOR'],
                                ];
                            } else {
                                if (isset($qtdDisponivelEnderecar[$recebido['COD_NORMA_PALETIZACAO']]))
                                    unset($qtdDisponivelEnderecar[$recebido['COD_NORMA_PALETIZACAO']]);
                            }
                        }
                    }
                }
            } else {
                $qtdDisponivelEnderecar = $qtdRecebida;
            }

            if (empty($qtdDisponivelEnderecar))
                throw new \Exception("O item $idProduto grade $grade já foi totalmente paletizado ou endereçado!");

            $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($recebimentoEn->getId(), null, $idProduto, $grade);

            foreach ($qtdDisponivelEnderecar as $item) {

                $this->deletaPaletesEmRecebimento($recebimentoEn->getId(), $idProduto, $grade);
                $idVolume = null;

                $volumes = [];

                if ($tipo == "V") {
                    $volumesArr = $volumesPalete = $this->getVolumesByOsAndNorma($idOs, $idProduto, $grade, $item['COD_NORMA_PALETIZACAO'], $recebimentoEn->getId());
                    foreach ($volumesArr as $vol) {
                        $volumes[] = $vol['COD_PRODUTO_VOLUME'];
                    }
                    $idVolume = $volumes[0];
                } else {
                    $volumesPalete = $this->getEmbalagensByOsAndNorma($idOs, $idProduto, $grade, 0, $recebimentoEn->getId());
                }

                $saldoPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($idProduto, $grade, $pickingEn->getId(), $volumes);
                $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($idProduto, $grade, $idVolume, $pickingEn->getId(), "E");
                $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($idProduto, $grade, $idVolume, $pickingEn->getId(), "S");
                $saldoPickingVirtual = Math::adicionar(Math::adicionar($saldoPickingReal, $reservaEntradaPicking), $reservaSaidaPicking);

                $espacoDisponivel = Math::subtrair($capacidadePicking, $saldoPickingVirtual);

                if ($espacoDisponivel <= 0)
                    throw new \Exception("O picking do produto $idProduto grade $grade não tem espaço disponivel para endereçamento!");

                $qtdEnderecar = 0;

                if (Math::compare($espacoDisponivel, $item['QTD'], ">")) {
                    $qtdEnderecar = $item['QTD'];
                } else {
                    if ($tipo == "V") {
                        $qtdEnderecar = $espacoDisponivel;
                    } else {
                        /** @var Produto\Embalagem $embalagenDefault */
                        $embalagenDefault = $produtoEn->getEmbalagens()->filter(
                            function($item) {
                                return (is_null($item->getDataInativacao()) && $item->getIsPadrao() == 'S');
                            }
                        )->first();

                        $resto = Math::resto($item['QTD'], $embalagenDefault->getQuantidade());
                        if ($resto > 0) {
                            $qtdEnderecar = $resto;
                        }
                        $qtdEnderecar += Math::multiplicar((int) Math::dividir(Math::subtrair($espacoDisponivel, $resto), $embalagenDefault->getQuantidade()), $embalagenDefault->getQuantidade());
                    }
                }

                if ($qtdEnderecar <= 0)
                    throw new \Exception("O produto $idProduto grade $grade não tem quantidade ou espaço disponível para endereçar!");

                /** @var Produto\NormaPaletizacao $normaEn */
                $unitizadorEn = $unitizadorRepo->find($item["COD_UNITIZADOR"]);

                $idNorma = $item['COD_NORMA_PALETIZACAO'];

                if ($idNorma == 0) {
                    $sql = "SELECT PDL.COD_NORMA_PALETIZACAO
                          FROM PRODUTO_EMBALAGEM PE
                          LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM 
                          LEFT JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = PDL.COD_NORMA_PALETIZACAO
                         WHERE PE.COD_PRODUTO = '$idProduto' 
                           AND PE.DSC_GRADE = '$grade'
                           AND NP.COD_UNITIZADOR = $item[COD_UNITIZADOR]";
                    $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    $idNorma = $result[0]['COD_NORMA_PALETIZACAO'];
                }

                $statusEn = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find(Palete::STATUS_RECEBIDO);

                $paleteEn = $this->salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumesPalete, $idNorma, $qtdEnderecar, $getDataValidadeUltimoProduto['dataValidade'], "M");
                $paletes[] = ["palete" => $paleteEn, "picking" => $pickingEn];
            }
        }
        $this->_em->flush();
        $this->_em->clear();

        foreach ($paletes as $item) {
            $this->alocaEnderecoPalete($item['palete']->getId(), $item['picking']->getId());
        }
        $this->_em->flush();
        return true;
    }

    public function deletaPaletesRecebidos($idRecebimento, $idProduto, $grade) {
        $ppRepository = $this->_em->getRepository("wms:Enderecamento\PaleteProduto");
        /** @var ReservaEstoqueEnderecamentoRepository $reservaEstoqueEndRepository */
        $reservaEstoqueEndRepository = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

        $statusRecebimento = Palete::STATUS_RECEBIDO;
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("pa")
                ->from("wms:Enderecamento\Palete", "pa")
                ->leftJoin("wms:Enderecamento\PaleteProduto", "pp", 'WITH', 'pp.uma = pa.id')
                ->leftJoin("wms:Produto\Embalagem", "pe", 'WITH', 'pe.id = pp.codProdutoEmbalagem')
                ->leftJoin("wms:Produto\Volume", "pv", 'WITH', 'pv.id = pp.codProdutoVolume')
                ->innerJoin("pa.recebimento", "r")
                ->innerJoin("pa.status", "s")
                ->where("r.id = '$idRecebimento'")
                ->andWhere("s.id = '$statusRecebimento'")
                ->andWhere("(pv.codProduto = '$idProduto' AND pv.grade = '$grade') OR (pe.codProduto = '$idProduto' AND pe.grade = '$grade')");
        $paletes = $query->getQuery()->getResult();
        foreach ($paletes as $key => $palete) {

            $produtos = $ppRepository->findBy(array('uma' => $palete->getId()));
            foreach ($produtos as $produto) {
                $this->getEntityManager()->remove($produto);
            }

            $reservaEstoqueEndRepository->removerReservaUMA($palete);

            $this->getEntityManager()->remove($palete);
        }
        $this->_em->flush();
    }

    public function getQtdEmRecebimento($idRecebimento, $idProduto, $grade) {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');

        $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        $qtdTotalReceb = $conferenciaRepo->getQtdByRecebimento($idRecebimento, $idProduto, $grade);
        $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($idRecebimento, $idProduto, $grade);

        $qtdTotalEnd = 0;
        if (count($produtoEn->getVolumes()) == 0) {
            foreach ($qtdEnderecada as $enderecado) {
                $qtdTotalEnd = $qtdTotalEnd + $enderecado['QTD'];
            }
        } else {
            if (count($qtdEnderecada) > 0) {
                $qtdTotalEnd = $qtdEnderecada[0]['QTD'];
            } else {
                $qtdTotalEnd = 0;
            }
            foreach ($qtdEnderecada as $enderecado) {
                if ($enderecado['QTD'] < $qtdTotalEnd) {
                    $qtdTotalEnd = $enderecado['QTD'];
                }
            }
        }

        $qtd = 0;
        foreach ($qtdTotalReceb as $recebido) {
            $qtd = $qtd + $recebido['qtd'];
        }

        return $qtd - $qtdTotalEnd;
    }

    public function gerarPaletes($idRecebimento, $idProduto, $grade, $throwException = true, $tipoEnderecamento = 'A') {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');
        $recebimentoEn = $this->getEntityManager()->getRepository('wms:Recebimento')->find($idRecebimento);
        $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO) {
            $codStatus = Palete::STATUS_RECEBIDO;
            $recebimentoFinalizado = true;
        } else if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_DESFEITO) {
            $codStatus = Palete::STATUS_CANCELADO;
            $recebimentoFinalizado = true;
        } else if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_CANCELADO) {
            $codStatus = Palete::STATUS_CANCELADO;
            $recebimentoFinalizado = true;
        } else {
            $codStatus = Palete::STATUS_EM_RECEBIMENTO;
            $recebimentoFinalizado = false;
        }
        $statusEn = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find($codStatus);
        $this->deletaPaletesEmRecebimento($recebimentoEn->getId(), $idProduto, $grade);
        if (count($produtoEn->getVolumes()) == 0) {
            $tipo = "E";
            $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($recebimentoEn->getId(), $idProduto, $grade, $tipo);
            $idOs = $conferenciaRepo->getLastOsRecebimentoEmbalagem($idRecebimento, $idProduto, $grade);
            $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoEmbalagemAndNorma($idOs, $idProduto, $grade);
        } else {
            $tipo = "V";
            $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($recebimentoEn->getId(), $idProduto, $grade, $tipo);
            $idOs = $conferenciaRepo->getLastOsRecebimentoVolume($idRecebimento, $idProduto, $grade);
            $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoVolumeAndNorma($idOs, $idProduto, $grade);
        }


        if (count($qtdRecebida) <= 0) {
            if ($throwException == true) {
                throw new Exception("O recebimento do produto $idProduto não possui unitizador ou ainda não foi conferido");
            }
        }

        $qtdTotalConferido = 0;
        $pesoTotalConferido = 0;
        foreach ($qtdRecebida as $recebido) {
            if ($tipo == "E") {
                $qtdTotalConferido = $qtdTotalConferido + $recebido['QTD'];
                $pesoTotalConferido += $recebido['PESO'];
            } else {
                if ($qtdTotalConferido == 0) {
                    $qtdTotalConferido = $recebido['QTD'];
                } else if ($recebido['QTD'] < $qtdTotalConferido) {
                    $qtdTotalConferido = $recebido['QTD'];
                }

                if ($pesoTotalConferido == 0) {
                    $pesoTotalConferido = $recebido['PESO'];
                } else if ($recebido['PESO'] < $pesoTotalConferido) {
                    $pesoTotalConferido = $recebido['PESO'];
                }
            }
        }

        foreach ($qtdEnderecada as $key => $enderecado) {
            $recebido = $qtdRecebida[$key];
            if ($recebido['COD_NORMA_PALETIZACAO'] == $enderecado['COD_NORMA_PALETIZACAO']
                && $recebido["LOTE"] == $enderecado["LOTE"]){
                $qtd = Math::subtrair($recebido['QTD'], $enderecado['QTD']);
                $peso = Math::subtrair($recebido['PESO'], $enderecado['PESO']);
                if ($qtd > 0 || $peso > 0) {
                    $qtdRecebida[$key]['QTD'] = $qtd;
                    $qtdRecebida[$key]['PESO'] = $peso;
                } else {
                    unset($qtdRecebida[$key]);
                }
            }
        }

        $qtdLimite = null;
        if ($recebimentoFinalizado == false) {
            $qtdLimite = $this->getQtdLimiteRecebimento($recebimentoEn->getId(), $idProduto, $grade, $qtdRecebida, $qtdEnderecada, $tipo);
        }

        $pesoLimite = null;
        if ($recebimentoFinalizado == false) {
            $pesoLimite = $this->getPesoLimiteRecebimento($recebimentoEn->getId(), $idProduto, $grade, $qtdRecebida, $qtdEnderecada, $tipo);
        }

        $this->salvaNovosPaletes($produtoEn, $qtdRecebida, $idProduto, $idOs, $grade, $recebimentoFinalizado, $qtdLimite, $tipo, $recebimentoEn, $statusEn, $qtdTotalConferido, $tipoEnderecamento, $pesoLimite, $pesoTotalConferido);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function getPesoLimiteRecebimento($codRecebimento, $codProduto, $grade, $qtdRecebida, $qtdEnderecada, $tipo) {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $nfRepo */
        $nfRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $pesoLimiteTotal = $nfRepo->getPesoByProdutoAndRecebimento($codRecebimento, $codProduto, $grade);
        if ($tipo == "V") {
            $pesoLimite = array();
            foreach ($qtdRecebida as $recebido) {
                $idNorma = $recebido['COD_NORMA_PALETIZACAO'];
                $pesoLimite[$idNorma] = $pesoLimiteTotal;
                foreach ($qtdEnderecada as $enderecado) {
                    if ($enderecado['COD_NORMA_PALETIZACAO'] == $idNorma) {
                        $pesoLimite[$idNorma] = $pesoLimiteTotal - $enderecado['PESO'];
                    }
                }
            }
            return $pesoLimite;
        } else {
            foreach ($qtdEnderecada as $enderecado) {
                $pesoLimiteTotal = $pesoLimiteTotal - $enderecado['PESO'];
            }
            return $pesoLimiteTotal;
        }
    }

    public function getQtdLimiteRecebimento($codRecebimento, $codProduto, $grade, $qtdRecebida, $qtdEnderecada, $tipo) {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $nfRepo */
        $nfRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $qtdLimiteTotal = $nfRepo->getQtdByProduto($codRecebimento, $codProduto, $grade);
        if ($tipo == "V") {
            $qtdLimite = array();
            foreach ($qtdRecebida as $recebido) {
                $idNorma = $recebido['COD_NORMA_PALETIZACAO'];
                $qtdLimite[$idNorma] = $qtdLimiteTotal;
                foreach ($qtdEnderecada as $enderecado) {
                    if ($enderecado['COD_NORMA_PALETIZACAO'] == $idNorma) {
                        $qtdLimite[$idNorma] = $qtdLimiteTotal - $enderecado['QTD'];
                    }
                }
            }
            return $qtdLimite;
        } else {
            foreach ($qtdEnderecada as $enderecado) {
                $qtdLimiteTotal = $qtdLimiteTotal - $enderecado['QTD'];
            }
            return $qtdLimiteTotal;
        }
    }

    public function salvaNovosPaletes($produtoEn, $qtdRecebida, $idProduto, $idOs, $grade, $recebimentoFinalizado, $qtdLimite, $tipo, $recebimentoEn, $statusEn, $qtdTotalConferido, $tipoEnderecamento = 'A', $pesoLimite = null, $pesoTotalConferido = null) {
        //QUANTIDADE DA NOTA
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $nfRepo */
        $nfRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $qtdNotaFiscal = $nfRepo->getQtdByProduto($recebimentoEn->getId(), $idProduto, $grade);

        $pesoTotal = 0;
        $arrayTemp = array();
        foreach ($qtdRecebida as $key => $dados){
            if(isset($arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['QTD'])) {
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['QTD'] = Math::adicionar($dados['QTD'], $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['QTD']);
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['PESO'] = Math::adicionar($dados['PESO'], $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['PESO']);
            }else{
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['QTD'] = $dados['QTD'];
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['PESO'] = $dados['PESO'];
            }
            $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['COD_NORMA_PALETIZACAO'] = $dados['COD_NORMA_PALETIZACAO'];
            $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['NUM_NORMA'] = $dados['NUM_NORMA'];
            $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['COD_UNITIZADOR'] = $dados['COD_UNITIZADOR'];
            if(isset($dados['LOTE'])) {
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['LOTE'][$key]['LOTE'] = $dados['LOTE'];
                $arrayTemp[$dados['COD_NORMA_PALETIZACAO']]['LOTE'][$key]['QTD'] = $dados['QTD'];
            }
        }
        foreach ($arrayTemp as $unitizador) {
            $idNorma = $unitizador['COD_NORMA_PALETIZACAO'];

            if ($idNorma == 0) {
                $sql = "SELECT PDL.COD_NORMA_PALETIZACAO
                          FROM PRODUTO_EMBALAGEM PE
                          LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM 
                          LEFT JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = PDL.COD_NORMA_PALETIZACAO
                         WHERE PE.COD_PRODUTO = '" . $produtoEn->getId() . "' 
                           AND PE.DSC_GRADE = '" . $produtoEn->getGrade() . "'
                           AND NP.COD_UNITIZADOR = " . $unitizador['COD_UNITIZADOR'];
                $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                $idNorma = $result[0]['COD_NORMA_PALETIZACAO'];
            }

            if ($unitizador['QTD'] > 0) {
                if ($unitizador['NUM_NORMA'] == 0) {
                    throw new Exception("O produto $idProduto não possui norma de paletização");
                }

                if ($tipo == "V") {
                    $volumes = $this->getVolumesByOsAndNorma($idOs, $idProduto, $grade, $idNorma, $recebimentoEn->getId());
                } else {
                    $volumes = $this->getEmbalagensByOsAndNorma($idOs, $idProduto, $grade, $idNorma, $recebimentoEn->getId());
                }
                $qtd = $unitizador['QTD'];
                $peso = $unitizador['PESO'];

                $getDataValidadeUltimoProduto = $nfRepo->buscaRecebimentoProduto($recebimentoEn->getId(), null, $idProduto, $grade);

                if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto)) {
                    $dataValidade = $getDataValidadeUltimoProduto['dataValidade'];
                } else {
                    $dataValidade = null;
                }

                //TRAVA PARA GERAR NO MAXIMO A QUANTIDADE TOTAL DA NOTA ENQUANTO O RECEBIMENTO NÃO TIVER SIDO FINALIZADO
                if ($recebimentoFinalizado == false) {
                    if ($tipo == "V") {
                        $qtdLimite[$idNorma] = $qtdLimite[$idNorma] - $qtd;
                        if ($qtdLimite[$idNorma] < 0) {
                            $qtd = $qtd + $qtdLimite[$idNorma];
                        }
                        $pesoLimite[$idNorma] = $pesoLimite[$idNorma] - $peso;
                        if ($pesoLimite[$idNorma] < 0) {
                            $peso = (float) $peso + $pesoLimite[$idNorma];
                        }
                    } else {
                        $qtdLimite = $qtdLimite - $qtd;
                        if ($qtdLimite < 0) {
                            $qtd = $qtd + $qtdLimite;
                        }

                        $pesoLimite = $pesoLimite - $peso;
                        if ($pesoLimite < 0) {
                            $peso = (float) $peso + $pesoLimite;
                        }
                    }
                }

                $qtdPaletes = $qtd / $unitizador['NUM_NORMA'];
                $qtdUltimoPalete = fmod($qtd, $unitizador['NUM_NORMA']);
                $unitizadorEn = $this->getEntityManager()->getRepository('wms:Armazenagem\Unitizador')->find($unitizador['COD_UNITIZADOR']);
                $pesoTotalPaletes = 0;
                if ($qtdPaletes > 0)
                    $pesoPorPalete = (float) ($peso / $qtdPaletes);
                if(isset($unitizador['LOTE'])) {
                    $vetLote = $unitizador['LOTE'];
                }

                $newPalete = function(){ return [ 'qtdTotal' => 0, 'lotes' => [] ]; };

                $paletesCompletos = [];
                $ultimoPalete = [];
                $qtdNoPalete = $unitizador['NUM_NORMA'];
                if (isset($vetLote) && !empty($vetLote)) {
                    $palete = $newPalete();
                    foreach ($vetLote as $lote) {
                        $qtdLote = $lote['QTD'];
                        while ($qtdLote > 0) {
                            $incremento = Math::adicionar($palete['qtdTotal'], $qtdLote);
                            if (Math::compare($incremento, $qtdNoPalete, '<=')) {
                                $palete['qtdTotal'] = $incremento;
                                $palete['lotes'][$lote['LOTE']] = $qtdLote;
                                if ($incremento == $qtdNoPalete) {
                                    $paletesCompletos[] = $palete;
                                    $palete = $newPalete();
                                }
                                $ultimoPalete = $palete;
                                $qtdLote = 0;
                            } else {
                                $excedente = Math::subtrair($incremento, $qtdNoPalete);
                                $palete['qtdTotal'] = $qtdNoPalete;
                                $palete['lotes'][$lote['LOTE']] = Math::subtrair($qtdLote, $excedente);
                                $paletesCompletos[] = $palete;
                                $qtdLote = $excedente;
                                $palete = $newPalete();
                            }
                        }
                    }
                }

                for ($i = 0; $i < floor($qtdPaletes); $i++) {
                    $pesoTotal += $pesoPorPalete;
                    $pesoTotalPaletes += $pesoPorPalete;
                    $lote = (isset($paletesCompletos[$i]) && !empty($paletesCompletos[$i])) ? $paletesCompletos[$i] : null;

                    $this->salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumes, $idNorma, $unitizador['NUM_NORMA'], $dataValidade, $tipoEnderecamento, $pesoPorPalete, $lote);
                }
                if ($qtdUltimoPalete > 0) {
                    //TRAVA PARA GERAR O PALETE COM A QUANTIDADE QUEBRADA SOMENTE SE TIVER FINALIZADO
                    if ($recebimentoFinalizado == true || ($qtdTotalConferido == $qtdNotaFiscal)) {
                        $pesoUltimoPalete = $peso - $pesoTotalPaletes;
                        if(!isset($arrayUltimoPaleteLote) && isset($vetLote)){
                            $this->salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumes, $idNorma, $qtdUltimoPalete, $dataValidade, $tipoEnderecamento, $pesoUltimoPalete, $ultimoPalete);
                        }else {
                            $this->salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumes, $idNorma, $qtdUltimoPalete, $dataValidade, $tipoEnderecamento, $pesoUltimoPalete);
                        }
                    }
                }
            }
        }
    }

    public function salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumes, $idNorma, $Qtd, $dataValidade, $tipoEnderecamento = 'A', $pesoPorPalete = null, $arrayPaleteLote = null)
    {
        if (!empty($dataValidade))
            $dataValidade = new \DateTime($dataValidade);
        $paleteEn = new Palete();
        $paleteEn->setRecebimento($recebimentoEn);
        $paleteEn->setUnitizador($unitizadorEn);
        $paleteEn->setStatus($statusEn);
        $paleteEn->setImpresso('N');
        $paleteEn->setDepositoEndereco(null);
        $paleteEn->setTipoEnderecamento($tipoEnderecamento);
        $paleteEn->setPeso($pesoPorPalete);
        $this->_em->persist($paleteEn);
        foreach ($volumes as $volume) {
            if (!empty($arrayPaleteLote)) {
                foreach ($arrayPaleteLote['lotes'] as $key => $qtd) {
                    $paleteProduto = new PaleteProduto();
                    $paleteProduto->setUma($paleteEn);
                    $paleteProduto->setCodNormaPaletizacao($idNorma);
                    $paleteProduto->setQtd($qtd);
                    $paleteProduto->setCodProduto($produtoEn->getId());
                    $paleteProduto->setGrade($produtoEn->getGrade());
                    $paleteProduto->setProduto($produtoEn);
                    $paleteProduto->setQtdEnderecada(0);
                    $paleteProduto->setCodProdutoEmbalagem($volume['COD_PRODUTO_EMBALAGEM']);
                    $paleteProduto->setCodProdutoVolume($volume['COD_PRODUTO_VOLUME']);
                    $paleteProduto->setValidade($dataValidade);
                    $paleteProduto->setLote($key);
                    $this->_em->persist($paleteProduto);
                }
            } else {
                $paleteProduto = new PaleteProduto();
                $paleteProduto->setUma($paleteEn);
                $paleteProduto->setCodNormaPaletizacao($idNorma);
                $paleteProduto->setQtd($Qtd);
                $paleteProduto->setCodProduto($produtoEn->getId());
                $paleteProduto->setGrade($produtoEn->getGrade());
                $paleteProduto->setProduto($produtoEn);
                $paleteProduto->setQtdEnderecada(0);
                $paleteProduto->setCodProdutoEmbalagem($volume['COD_PRODUTO_EMBALAGEM']);
                $paleteProduto->setCodProdutoVolume($volume['COD_PRODUTO_VOLUME']);
                $paleteProduto->setValidade($dataValidade);
                $this->_em->persist($paleteProduto);
            }
        }
        return $paleteEn;
    }

    public function finalizar(array $paletes, $idPessoa, $formaConferencia = OrdemServicoEntity::MANUAL, $dataValidade = null) {
        if (count($paletes) <= 0 || empty($idPessoa)) {
            throw new Exception('Usuario ou palete não informados');
        }

        $retorno = array();
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        $reservaEstoqueRepo->validaOperacaoExpedicaoEmFinalizacao(implode(", ", $paletes), "U" );

        $ok = false;
        $arrPaletesResult = array();
        $recebimentos = [];
        foreach ($paletes as $paleteId) {
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->find($paleteId);

            $idRecebimento = $paleteEn->getRecebimento()->getId();
            if (!in_array($idRecebimento, $recebimentos))
                $recebimentos[] = $idRecebimento;

            if ($paleteEn->getCodStatus() == Palete::STATUS_CANCELADO) {
                $arrPaletesResult[] = $paleteId;
            } elseif ($paleteEn->getCodStatus() == Palete::STATUS_ENDERECADO &&
                    $paleteEn->getRecebimento()->getStatus()->getId() != \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO
            ) {
                $arrPaletesResult[] = $paleteId;
            } else {

                if (!empty($dataValidade['dataValidade'])) {
                    $dataValidade['dataValidade'] = new\DateTime($dataValidade['dataValidade']);
                }

                if ($formaConferencia == OrdemServicoEntity::COLETOR || $paleteEn->getCodStatus() == Palete::STATUS_EM_ENDERECAMENTO) {
                    $paleteEn->setCodStatus(Palete::STATUS_ENDERECADO);
                    $paleteEn->setValidade($dataValidade['dataValidade']);
                    $this->_em->persist($paleteEn);
                    $retorno = $this->criarOrdemServico($paleteId, $idPessoa, $formaConferencia);
                }

                if (!empty($retorno)) {
                    $ok = true;
                    $this->getEntityManager()->flush();
                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                        $idEstoque = $paleteEn->getDepositoEndereco()->getId();
                        $produtosArray = $paleteEn->getProdutosArray();
                        $idPalete = $paleteEn->getId();
                        $idUnitizador = $paleteEn->getUnitizador()->getId();
                        $this->getEntityManager()->clear();
                        $reservaEstoqueRepo->efetivaReservaEstoque($idEstoque, $produtosArray, "E", "U", $idPalete, $idPessoa, $retorno['id'], $idUnitizador, null, $dataValidade);
                    }
                }
            }
        }

        $this->_em->flush();

        if ($this->getSystemParameterValue('CONTROLE_PROPRIETARIO') == 'S') {
            foreach ($recebimentos as $id) {
                $this->_em->getRepository(ReservaEstoqueProprietario::class)->checkLiberacaoReservas($id, true);
            }
        }

        if (!empty($arrPaletesResult)) {
            if (count($arrPaletesResult) > 1) {
                $strPaletes = implode(", ", $arrPaletesResult);
                return "Os paletes $strPaletes não tiveram as reservas de estoque atendidas";
            } else {
                return "O palete $arrPaletesResult[0] não teve a reserva de estoque atendida";
            }
        }

        return $ok;
    }

    public function criarOrdemServico($idEnderecamento, $idPessoa, $formaConferencia) {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');

        // cria ordem de servico
        $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
            'identificacao' => array(
                'tipoOrdem' => 'enderecamento',
                'idEnderecamento' => $idEnderecamento,
                'idAtividade' => AtividadeEntity::ENDERECAMENTO,
                'formaConferencia' => $formaConferencia,
                'idPessoa' => $idPessoa
            ),
        ));

        return array(
            'criado' => true,
            'id' => $idOrdemServico,
            'mensagem' => 'Ordem de Serviço Nº ' . $idOrdemServico . ' criada com sucesso.',
        );
    }

    public function alocaEnderecoPaleteByBlocado($idPalete, $idEndereco) {
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        if ($paleteEn == NULL) {
            throw new \Exception("Palete $idPalete não encontrado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_ENDERECADO) {
            throw new \Exception("Palete $idPalete já endereçado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_CANCELADO) {
            throw new \Exception("Palete $idPalete cancelado");
        }

        $endereco = $this->_em->getReference("wms:Deposito\Endereco", $idEndereco);

        $paleteEn->setDepositoEndereco($endereco);
        $paleteEn->setCodStatus($paleteEn::STATUS_EM_ENDERECAMENTO);
        $paleteEn->setImpresso("N");

        $this->getEntityManager()->persist($paleteEn);
        $this->_em->flush();
    }

    public function alocaEnderecoPalete($idPalete, $idEndereco, $repositorios = null) {

        if ($repositorios == null) {
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        } else {
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoRepo */
            $enderecoRepo = $repositorios['enderecoRepo'];
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        }

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->find($idPalete);
        if ($paleteEn == NULL) {
            throw new \Exception("Palete $idPalete não encontrado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_ENDERECADO) {
            throw new \Exception("Palete $idPalete já endereçado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_CANCELADO) {
            throw new \Exception("Palete $idPalete cancelado");
        }

        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoNovoEn = $enderecoRepo->find($idEndereco);
        $enderecoAntigoEn = $paleteEn->getDepositoEndereco();

        $arrayProdutos = $paleteEn->getProdutosArray();

        if ($enderecoAntigoEn != NULL) {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigoEn, $qtdAdjacente, "LIBERAR", $paleteEn->getId());
            $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(), $arrayProdutos, "E", "U", $paleteEn->getId());
            if ($enderecoAntigoEn->getId() != $enderecoNovoEn->getId()) {
                $paleteEn->setImpresso("N");
            }
        } else {
            $paleteEn->setImpresso("N");
        }
        $paleteEn->setDepositoEndereco($enderecoNovoEn);
        $paleteEn->setCodStatus($paleteEn::STATUS_EM_ENDERECAMENTO);
        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoNovoEn, $qtdAdjacente, "OCUPAR");
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoNovoEn->getId(), $arrayProdutos, "E", "U", $paleteEn->getId());

        $this->getEntityManager()->persist($paleteEn);
    }

    public function getPaletesReport($values) {
        extract($values);

        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("pa.id coduma,
                      r.id codrecebimento,
                      pp.qtd quantidade,
                      prod.id codproduto,
                      prod.descricao nomeproduto,
                      s.sigla status,
                      dep.descricao endereco"
                )
                ->from("wms:Enderecamento\Palete", "pa")
                ->innerJoin("pa.produtos", "pp")
                ->innerJoin("pp.produto", "prod")
                ->innerJoin("pa.recebimento", "r")
                ->innerJoin("r.status", "s")
                ->leftJoin("pa.depositoEndereco", "dep");

        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2))) {
            $dataInicial1 = str_replace("/", "-", $dataInicial1);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $dataInicial2);
            $dataI2 = new \DateTime($dataInicial2);

            $query->where("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                    ->setParameter(1, $dataI1)
                    ->setParameter(2, $dataI2);
        }

        if (isset($dataFinal1) && (!empty($dataFinal1)) && (!empty($dataFinal2))) {
            $DataFinal1 = str_replace("/", "-", $dataFinal1);
            $dataF1 = new \DateTime($DataFinal1);

            $DataFinal2 = str_replace("/", "-", $dataFinal2);
            $dataF2 = new \DateTime($DataFinal2);

            $query->andWhere("((TRUNC(r.dataFinal) >= ?3 AND TRUNC(r.dataFinal) <= ?4) OR r.dataFinal IS NULL")
                    ->setParameter(3, $dataF1)
                    ->setParameter(4, $dataF2);
        }

        if (isset($status) && (!empty($status))) {
            $query->andWhere("r.status = ?5")
                    ->setParameter(5, $status);
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            $query->andWhere("r.id = ?6")
                    ->setParameter(6, $idRecebimento);
        }

        $relatorio_uma = $query->getQuery()->getArrayResult();
        return $relatorio_uma;
    }

    /**
     * @param $paleteEn Palete
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cancelaPalete($paleteEn) {

        if ($paleteEn == NULL) {
            throw new \Exception("Palete não encontrado");
        }

        try {
            if ($paleteEn->getCodStatus() == Palete::STATUS_ENDERECADO) {

                $enderecoEn = $paleteEn->getDepositoEndereco();
                $idUma = $paleteEn->getId();
                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
                $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");

                $params = array();
                $params['tipo'] = HistoricoEstoque::TIPO_ENDERECAMENTO;

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

                foreach ($paleteEn->getProdutos() as $produto) {
                    $params['produto'] = $produto->getProduto();
                    $params['endereco'] = $enderecoEn;
                    $params['qtd'] = $produto->getQtd() * -1;
                    $params['observacoes'] = "Mov. ref. cancelamento do Palete " . $idUma;

                    if ($produto->getCodProdutoEmbalagem()) {
                        $params['embalagem'] = $embalagemRepo->findOneBy(array('id' => $produto->getCodProdutoEmbalagem()));
                    } else {
                        $params['volume'] = $volumeRepo->findOneBy(array('id' => $produto->getCodProdutoVolume()));
                    }

                    if ($paleteEn->getRecebimento()->getStatus()->getId() == Recebimento::STATUS_FINALIZADO) {
                        $estoqueRepo->movimentaEstoque($params);
                    }
                }
            }

            /** @var ReservaEstoqueEnderecamentoRepository $reservaEndRepo */
            $reservaEndRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

            /** @var ReservaEstoqueEnderecamento $reservaEnd */
            $reservaEnd = $reservaEndRepo->removerReservaUMA($paleteEn);

            $paleteEn->setCodStatus(Palete::STATUS_CANCELADO);

            $this->getEntityManager()->persist($paleteEn);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $idUma
     * @return bool
     * @throws \Exception
     */
    public function desfazerPalete($idUma) {

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->findOneBy(array('id' => $idUma));

        if ($paleteEn == NULL) {
            throw new \Exception("Palete $idUma não encontrado");
        }

        $idUma = $paleteEn->getId();
        try {
            switch ($paleteEn->getCodStatus()) {
                case Palete::STATUS_ENDERECADO:
                    $idEndereco = $paleteEn->getDepositoEndereco()->getId();

                    $reservaEstoqueRepo->reabrirReservaEstoque($idEndereco, $paleteEn->getProdutosArray(), "E", "U", $idUma);
                    $paleteEn->setCodStatus(Palete::STATUS_EM_ENDERECAMENTO);
                    $this->_em->persist($paleteEn);

                    $ordensServicoEn = $this->_em->getRepository('wms:OrdemServico')->findBy(array('idEnderecamento' => $paleteEn->getId()));
                    foreach ($ordensServicoEn as $osEn) {
                        if ($osEn->getDscObservacao() == NULL) {
                            $osEn->setDscObservacao('Endereçamento desfeito');
                            $this->getEntityManager()->persist($osEn);
                        }
                    }
                    break;
                case Palete::STATUS_EM_ENDERECAMENTO:
                    $idEndereco = $paleteEn->getDepositoEndereco()->getId();

                    if ($paleteEn->getRecebimento()->getStatus()->getId() == Recebimento::STATUS_FINALIZADO) {
                        $codStatus = Palete::STATUS_RECEBIDO;
                    } else {
                        $codStatus = Palete::STATUS_EM_RECEBIMENTO;
                    }

                    $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
                    $enderecoAntigo = $paleteEn->getDepositoEndereco();
                    if ($enderecoAntigo != NULL) {
                        $enderecoRepo = $this->_em->getRepository("wms:Deposito\Endereco");
                        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo, $qtdAdjacente, "LIBERAR", $paleteEn->getId());
                        $reservaEstoqueRepo->cancelaReservaEstoque($idEndereco, $paleteEn->getProdutosArray(), "E", "U", $idUma);
                    }

                    $paleteEn->setDepositoEndereco(NULL);
                    $paleteEn->setImpresso("N");
                    $paleteEn->setCodStatus($codStatus);
                    $this->_em->persist($paleteEn);
                    break;
                case Palete::STATUS_RECEBIDO:
                    /** @var ReservaEstoqueEnderecamentoRepository $reservaEstoqueEnderecamentoRepo */
                    $reservaEstoqueEnderecamentoRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

                    $reservaEstoqueEnderecamentoRepo->removerReservaUMA($paleteEn);

                    $this->_em->remove($paleteEn);
                    break;
                case Palete::STATUS_EM_RECEBIMENTO:
                    $this->_em->remove($paleteEn);
                    break;
            }
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $e) {
            throw $e;
        }
        return true;
    }

    public function getImprimeNorma($idRecebimento, $idProduto, $grade) {
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("u.descricao")
                ->from("wms:Enderecamento\Palete", "pa")
                ->innerJoin("pa.produtos", "pp")
                ->innerJoin("pa.unitizador", "u")
                ->innerJoin("pp.produto", "prod")
                ->innerJoin("pa.recebimento", "r")
                ->where("r.id = $idRecebimento")
                ->andWhere("prod.id = '$idProduto'")
                ->andWhere("prod.grade = '$grade'");

        $array = $query->getQuery()->getArrayResult();

        $norma = (!empty($array))? $array[0]['descricao']: null;
        return $norma;
    }

    public function getByRecebimentoAndStatus($recebimento, $status = Palete::STATUS_CANCELADO) {
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("pa.id, u.descricao unitizador, pa.qtd, sigla.sigla status, de.descricao endereco, pa.impresso")
                ->from("wms:Enderecamento\Palete", "pa")
                ->innerJoin('pa.unitizador', 'u')
                ->innerJoin('pa.status', 'sigla')
                ->leftJoin('pa.depositoEndereco', 'de')
                ->where("pa.status = " . $status);

        if ($recebimento) {
            $query->andWhere('pa.recebimento = :recebimento')
                    ->setParameter('recebimento', $recebimento);
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param $recebimento
     * @param $codProduto
     * @param $grade
     * @return bool
     * @throws \Exception
     */
    public function validaTroca($recebimento, $codProduto, $grade) {
        $params = array('id' => $recebimento,
            'codigo' => $codProduto,
            'grade' => $grade);

        $paletes = $this->getPaletesByProdutoAndGrade($params);
        foreach ($paletes as $palete) {
            if ($palete['impresso'] == 'S') {
                throw new \Exception("Existem paletes já impressos para este produto no novo recebimento");
            }
            if (($palete['codStatus'] == Palete::STATUS_ENDERECADO) || ($palete['codStatus'] == Palete::STATUS_EM_ENDERECAMENTO)) {
                throw new \Exception( "Existem paletes em endereçamento ou endereçados para este produto no novo recebimento");
            }
        }

        return true;
    }

    /**
     * @param $novoRecebimento
     * @param array $umas
     * @param $recebimentoAntigo
     * @param $codProduto
     * @param $grade
     * @throws \Exception
     */
    public function realizaTroca($novoRecebimento, array $umas, $recebimentoAntigo, $codProduto, $grade) {
        $this->getEntityManager()->beginTransaction();
        try {

            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
            $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $usuarioRepo->find($idUsuario);

            //DELETA OS PALETES DESNECESSARIOS NO NOVO RECEBIMENTO
            $this->deletaPaletesEmRecebimento($novoRecebimento, $codProduto, $grade);
            $this->deletaPaletesRecebidos($novoRecebimento, $codProduto, $grade);

            //TROCA OS PALETES DO RECEBIMENTO AUTAL PARA O NOVO RECEBIMENTO
            foreach ($umas as $uma) {
                //TROCO A UMA PARA O NOVO RECEBIMENTO
                $entity = $this->find($uma);
                $entRecebimento = $this->_em->getReference('wms:Recebimento', $novoRecebimento);
                $entity->setRecebimento($entRecebimento);

                $produtos = $entity->getProdutos();
                $produtoEn = $produtos[0]->getProduto();

                //EFETIVO A RESERVA DE ESTOQUE CASO NECESSARIO
                if ($entRecebimento->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO
                    && $entity->getStatus()->getId() == Palete::STATUS_ENDERECADO) {
                    /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                    $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

                    $reservaEstoqueEnderecamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");
                    $reservaEstoque = $reservaEstoqueEnderecamentoRepo->findOneBy(array('palete' => $uma));

                    $reservaEstoqueEn = $reservaEstoque->getReservaEstoque();
                    if ($reservaEstoqueEn->getAtendida() == 'N') {
                        $reservaEstoqueRepo->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn, "E", $uma, $usuarioEn);
                    }
                }

                $this->_em->persist($entity);

                //GRAVO ANDAMENTO FALANDO QUE REALIZOU A TROCA DO RECEBIMENTO
                $andamento = new Andamento();
                $andamento->setDataAndamento(new \DateTime());
                $andamento->setProduto($produtoEn);
                $andamento->setUsuario($usuarioEn);
                $andamento->setRecebimento($entRecebimento);
                $andamento->setDscObservacao("UMA " . $uma . " trocada do recebimento " . $recebimentoAntigo . " para o recebimento " . $novoRecebimento);
                $this->getEntityManager()->persist($andamento);
            }

            $this->_em->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    public function getPaletesByProdutoAndGrade($params) {
        $grade = urldecode($params['grade']);
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("pa.id, u.descricao unitizador, pp.qtd, sigla.sigla status, de.descricao endereco, pa.impresso, pa.codStatus")
                ->from("wms:Enderecamento\Palete", "pa")
                ->innerJoin('pa.unitizador', 'u')
                ->innerJoin('pa.recebimento', 'receb')
                ->innerJoin('pa.status', 'sigla')
                ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = pa.id')
                ->leftJoin('pa.depositoEndereco', 'de')
                ->setParameter('recebimento', $params['id'])
                ->setParameter('produto', $params['codigo'])
                ->setParameter('grade', $grade)
                ->andWhere('pp.codProduto = :produto')
                ->andWhere('pp.grade = :grade')
                ->andWhere('pa.recebimento = :recebimento')
                ->distinct(true);

        if (isset($params['idStatus']) && (!is_null($params['idStatus']))) {
            $query->setParameter('idStatus', $params['idStatus'])
                    ->andWhere('p.codStatus = :idStatus');
        }

        if (isset($params['impresso']) && (!is_null($params['impresso']))) {
            $query->setParameter('indImpresso', $params['impresso'])
                    ->andWhere('p.impresso = :indImpresso');
        }

        return $query->getQuery()->getResult();
    }

    /*
     * Método para pegar o endereço sugerido para o determinado palete
     * Primeiro verifica se é um palete de sobra ou palete completo
     * Se for palete de sobra, tenta endereçar primeiro no picking se couber
     * Se for palete completo ou palete de sobra que não coube no picking, então pega a sugestão de endereço baseada nos cadastros
     * Retorna no final do método, o endereço sugerido

     * @param $paleteEn = Entidade de Enderecamento\Palete
     * @param $repositorios = array(
      'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
      'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
      'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
      'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
      'recebimentoRepo'         => $this->getEntityManager()->getRepository('wms:Recebimento'),
      'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'),
      )
     */

    public function getSugestaoEnderecoPalete($paleteEn, $repositorios) {

        /** @var \Wms\Domain\Entity\Produto\NormaPaletizacaoRepository $normaPaletizacaoRepo */
        $normaPaletizacaoRepo = $repositorios['normaPaletizacaoRepo'];
        $estoqueRepo = $repositorios['estoqueRepo'];
        $produtosPalete = $paleteEn->getProdutos();
        $possuiValidade = $produtosPalete[0]->getProduto()->getValidade();
        $codProduto = $produtosPalete[0]->getCodProduto();
        $grade = $produtosPalete[0]->getGrade();
        $qtdPaleteProduto = $produtosPalete[0]->getQtd();
        $codNormaPaletizacao = $produtosPalete[0]->getCodNormaPaletizacao();
        $normaPaletizacaoEn = $normaPaletizacaoRepo->findOneBy(array('id' => $codNormaPaletizacao));
        $larguraPalete = $paleteEn->getUnitizador()->getLargura(false) * 100;

        $sugestaoEndereco = null;

        //SE FOR UM PALETE DE SOBRA, ENTÃO TENTO ALOCAR PRIMEIRO NO PICKING
        if ($normaPaletizacaoEn->getNumNorma() > $qtdPaleteProduto) {
            if($possuiValidade == 'N') {
                $sugestaoEndereco = $this->getSugestaoEnderecoPicking($codProduto, $grade, $produtosPalete, $repositorios);
            }else{
                $validadeProduto = $produtosPalete[0]->getValidade()->format('Y-m-d');
                $dthMinPulmao = $estoqueRepo->getMenorValidadePulmao($codProduto, $grade);
                if(empty($dthMinPulmao) || (strtotime($validadeProduto) <= strtotime($dthMinPulmao['DATA']))){
                    $sugestaoEndereco = $this->getSugestaoEnderecoPicking($codProduto, $grade, $produtosPalete, $repositorios);
                }
            }
        }

        //SE FOR UM PALETE COMPLETO OU PALETE DE SOBRA QUE NÃO COUBE NO PICKING, VAI BUSCAR UM ENDEREÇO NO PULMÃO
        if ($sugestaoEndereco == null) {
            $sugestaoEndereco = $this->getSugestaoEnderecoPulmao($codProduto, $grade, $paleteEn->getRecebimento()->getId(), $larguraPalete, $repositorios);
        }
        return $sugestaoEndereco;
    }

    public function getSugestaoEnderecoPicking($codProduto, $grade, $produtosPalete, $repositorios) {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $repositorios['estoqueRepo'];
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];

        $embalagem = $produtosPalete[0]->getEmbalagemEn();
        $qtdPaleteProduto = $produtosPalete[0]->getQtd();

        /** @var Endereco $pickingEn */
        $pickingEn = $embalagem->getEndereco();

        $capacidadePicking = $embalagem->getCapacidadePicking();

        //VALIDO A CAPACIDADE DE PICKING SOMENTE SE O PRODUTO TIVER PICKING
        if ($pickingEn != null) {

            if ($pickingEn->isBloqueadaEntrada()) return null;

            $idVolume = null;
            $volumes = array();
            if ($produtosPalete[0]->getCodProdutoVolume() != NULL) {
                $idVolume = $produtosPalete[0]->getCodProdutoVolume();
                foreach ($produtosPalete as $volume) {
                    $volumes[] = $volume->getCodProdutoVolume();
                }
            }

            $SaldoPicking = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto, $grade, $pickingEn->getId(), $volumes);
            $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $pickingEn->getId(), "E");
            $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $pickingEn->getId(), "S");

            //ENDEREÇO NO PICKING SOMENTE SE A QUANTIDADE DO PALETE + O ESTOQUE NÂO PASSAR A CAPACIDADE
            if (($SaldoPicking + $reservaEntradaPicking + $reservaSaidaPicking + $qtdPaleteProduto) <= $capacidadePicking) {
                $sugestaoEndereco = array(
                    'COD_DEPOSITO_ENDERECO' => $pickingEn->getId(),
                    'DSC_DEPOSITO_ENDERECO' => $pickingEn->getDescricao()
                );
                return $sugestaoEndereco;
            }
        }

        return null;
    }

    /*
     * Retornar um unico endereço de sugestão para endereçamento baseado no cadastro do produto
     * Verifico primeiro as configurações do cadastro do produto, se as mesmas estiverem em branca, então verifico as configurações do modelo de endereçamento

     * ORDENAÇÂO ATUAL DA VARREDURA DOS ENDEREÇOS
     * 1-> Caracteristica de Endereço (Picking/Pulmão)
     * 2-> Estrutura de Armazenagem (Porta Palete/Blocado/Mezanino)
     * 3-> Area de Armazenagem
     * 4-> Tipo de Endereço (Meio/Inteiro/Inteiro Especial)
     * 5-> Menor Espaço Disponivel no Deposito (Melhorar Ocupação do Depósito)
     * 6-> Proximidade de Picking (Rua, Predio, Nivel e Apartamento)

     */

    public function getSugestaoEnderecoPulmao($codProduto, $dscGrade, $codRecebimento, $tamanhoPalete, $repositorios) {

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $repositorios['produtoRepo'];
        $recebimentoRepo = $repositorios['recebimentoRepo'];
        $modeloEnderecamentoRepo = $repositorios['modeloEnderecamentoRepo'];
        $produtoEn = $produtoRepo->findOneBy(array('id' => $codProduto, 'grade' => $dscGrade));

        //PEGA O MODELO DE ENDEREÇAMENTO PARA USO FUTURO NA FUNÇÃO
        $recebimentoEn = $recebimentoRepo->findOneBy(array('id' => $codRecebimento));
        if ($recebimentoEn->getModeloEnderecamento() != null) {
            $codModelo = $recebimentoEn->getModeloEnderecamento()->getId();
        } else {
            $codModelo = $this->getSystemParameterValue('MODELO_ENDERECAMENTO_PADRAO');
        }
        $modeloEnderecamentoEn = $modeloEnderecamentoRepo->findOneBy(array('id' => $codModelo));

        //PEGO O ENDEREÇO DE REFERENCIA DE PROXIMIDADE
        $enderecoReferencia = $produtoRepo->getEnderecoReferencia($produtoEn, $modeloEnderecamentoEn);
        if ($enderecoReferencia != null) {
            $ruaReferencia = $enderecoReferencia->getRua();
            $predioReferencia = $enderecoReferencia->getPredio();
            $nivelReferencia = $enderecoReferencia->getNivel();
            $apartamentoReferencia = $enderecoReferencia->getApartamento();
        } else {
            return null;
        }

        //VERIFICO SE O PRODUTO TEM ALGUMA CONFIGURAÇÃO DE PRIORIDADE POR AREA DE ARMAZENAGEM, SE TIVER USO A DO PRODUTO, CASO CONTRARIO USO A DO MODELO
        $endAreaArmazenagem = $produtoRepo->getSequenciaEndAutomaticoAreaArmazenagem($codProduto, $dscGrade, true);
        if (count($endAreaArmazenagem) > 0) {
            $sqlArea = " INNER JOIN PRODUTO_END_AREA_ARMAZENAGEM AA
                            ON AA.COD_PRODUTO = '$codProduto' AND AA.DSC_GRADE = '$dscGrade'
                           AND AA.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM";
        } else {
            $sqlArea = " INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE , COD_AREA_ARMAZENAGEM
                                       FROM MODELO_END_AREA_ARMAZ
                                      WHERE COD_MODELO_ENDERECAMENTO = $codModelo) AA
                                 ON AA.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM";
        }

        //VERIFICO SE O PRODUTO TEM ALGUMA CONFIGURAÇÃO DE PRIORIDADE POR TIPO DE ENDEREÇO, SE TIVER USO A DO PRODUTO, CASO CONTRARIO USO A DO MODELO
        $endTipoEndereco = $produtoRepo->getSequenciaEndAutomaticoTpEndereco($codProduto, $dscGrade, true);
        if (count($endTipoEndereco) > 0) {
            $sqlTipoEndereco = " INNER JOIN PRODUTO_END_TIPO_ENDERECO TE
                                    ON TE.COD_PRODUTO = '$codProduto' AND TE.DSC_GRADE = '$dscGrade'
                                   AND TE.COD_TIPO_ENDERECO = DE.COD_TIPO_ENDERECO";
        } else {
            $sqlTipoEndereco = "INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE, COD_TIPO_ENDERECO
                                              FROM MODELO_END_TIPO_ENDERECO
                                             WHERE COD_MODELO_ENDERECAMENTO = $codModelo) TE
                                        ON TE.COD_TIPO_ENDERECO = DE.COD_TIPO_ENDERECO";
        }

        //VERIFICO SE O PRODUTO TEM ALGUMA CONFIGURAÇÃO DE PRIORIDADE POR TIPO DE ESTRUTURA, SE TIVER USO A DO PRODUTO, CASO CONTRARIO USO A DO MODELO
        $endTipoEstrutura = $produtoRepo->getSequenciaEndAutomaticoTpEstrutura($codProduto, $dscGrade, true);
        if (count($endTipoEstrutura) > 0) {
            $sqlTipoEstrutura = " INNER JOIN PRODUTO_END_TIPO_EST_ARMAZ ET
                                     ON ET.COD_PRODUTO = '$codProduto' AND ET.DSC_GRADE = '$dscGrade'
                                    AND ET.COD_TIPO_EST_ARMAZ = DE.COD_TIPO_EST_ARMAZ";
        } else {
            $sqlTipoEstrutura = " INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE, COD_TIPO_EST_ARMAZ
                                                FROM MODELO_END_EST_ARMAZ
                                               WHERE COD_MODELO_ENDERECAMENTO = $codModelo) ET
                                          ON ET.COD_TIPO_EST_ARMAZ = DE.COD_TIPO_EST_ARMAZ";
        }

        //VERIFICO SE O PRODUTO TEM ALGUMA CONFIGURAÇÃO DE PRIORIDADE POR TIPO DE ESTRUTURA, SE TIVER USO A DO PRODUTO, CASO CONTRARIO USO A DO MODELO
        $endCaracEndereco = $produtoRepo->getSequenciaEndAutomaticoCaracEndereco($codProduto, $dscGrade, true);
        if (count($endCaracEndereco) > 0) {
            $sqlCaracEndereco = " INNER JOIN PRODUTO_END_CARACT_END CE
                                     ON CE.COD_PRODUTO = '$codProduto' AND CE.DSC_GRADE = '$dscGrade'
                                    AND CE.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO";
        } else {
            $sqlCaracEndereco = "INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE, COD_CARACTERISTICA_ENDERECO
                                               FROM MODELO_END_CARACT_END
                                              WHERE COD_MODELO_ENDERECAMENTO = $codModelo) CE
                                         ON CE.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO";
        }

        //EXECUTA QUERY PARA VERIFICAR ENDEREÇOS DISPONIVEIS
        $SQL = " SELECT DE.COD_DEPOSITO_ENDERECO,
                        DE.DSC_DEPOSITO_ENDERECO,
                        ABS(DE.NUM_RUA - $ruaReferencia) as DIF_RUA,
                        ABS(DE.NUM_PREDIO - $predioReferencia) as DIF_PREDIO,
                        ABS(DE.NUM_NIVEL - $nivelReferencia) as DIF_NIVEL,
                        ABS(DE.NUM_APARTAMENTO - $apartamentoReferencia) as DIF_APARTAMENTO,
                        (LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO) as LARG_DISPONIVEL
                   FROM DEPOSITO_ENDERECO DE
                  INNER JOIN V_OCUP_RESERVA_LONGARINA LONGARINA
                     ON LONGARINA.NUM_PREDIO  = DE.NUM_PREDIO
                    AND LONGARINA.NUM_NIVEL   = DE.NUM_NIVEL
                    AND LONGARINA.NUM_RUA     = DE.NUM_RUA
                  $sqlArea
                  $sqlTipoEndereco
                  $sqlTipoEstrutura
                  $sqlCaracEndereco
                  WHERE DE.IND_ATIVO = 'S'
                    AND ((DE.COD_CARACTERISTICA_ENDERECO  != 37) OR (DE.COD_TIPO_EST_ARMAZ = 26))
                    AND ((LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO) >= $tamanhoPalete)
                    AND DE.IND_DISPONIVEL = 'S'
                    AND DE.BLOQUEADA_ENTRADA = 0
               ORDER BY CE.NUM_PRIORIDADE,
                        ET.NUM_PRIORIDADE,
                        AA.NUM_PRIORIDADE,
                        TE.NUM_PRIORIDADE,
                        LARG_DISPONIVEL,
                        DIF_RUA,
                        DIF_PREDIO,
                        DIF_NIVEL,
                        DE.NUM_APARTAMENTO";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        //RETORNA UM UNICO ENDEREÇO DE SUGESTÃO
        if (count($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function alterarNorma($codProduto, $grade, $idRecebimento, $idUma) {

        $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");
        $conferenciaRepo = $this->getEntityManager()->getRepository("wms:Recebimento\Conferencia");

        $result = $this->getEntityManager()->getRepository("wms:Produto")->getNormaPaletizacaoPadrao($codProduto, $grade);
        $idNorma = $result['idNorma'];

        if ($idNorma == NULL) {
            $this->addFlashMessage('error', "O Produto $codProduto, grade $grade não possuí norma de paletização");
            return false;
        }

        /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimento $recebimentoEn */
        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento\VQtdRecebimento")->findOneBy(array('codRecebimento' => $idRecebimento, 'codProduto' => $codProduto, 'grade' => $grade));
        $conferenciaEn = $conferenciaRepo->findOneBy(array('recebimento' => $idRecebimento, 'codProduto' => $codProduto, 'grade' => $grade));

        if (($recebimentoEn == NULL) && ($conferenciaEn == NULL)) {
            $this->addFlashMessage('error', "Nenhuma quantidade conferida para o produto $codProduto, grade $grade");
            return false;
        }

        try {
            if ($recebimentoEn == null) {
                $idOs = $conferenciaRepo->getLastOsConferencia($idRecebimento, $codProduto, $grade);
                $idNormaAntiga = 'Nenhuma Norma';
                $qtdNormaAntiga = 0;
            } else {
                $normaAntigaEn = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id' => $recebimentoEn->getCodNormaPaletizacao()));
                if ($normaAntigaEn == null) {
                    $idNormaAntiga = "";
                    $qtdNormaAntiga = "SEM NORMA ANTIGA";
                } else {
                    $idNormaAntiga = $normaAntigaEn->getId();
                    $qtdNormaAntiga = $normaAntigaEn->getNumNorma();
                }

                $idOs = $recebimentoEn->getCodOs();
            }

            $recebimentoRepo->alteraNormaPaletizacaoRecebimento($idRecebimento, $codProduto, $grade, $idOs, $idNorma);

            /** @var \Wms\Domain\Entity\Enderecamento\AndamentoRepository $andamentoRepo */
            $andamentoRepo = $this->_em->getRepository('wms:Enderecamento\Andamento');
            $msg = "Norma de paletização trocada com sucesso para a da unidade " . $result['unidade'] . " (" . $result['unitizador'] . ")  | Norma: " . $idNormaAntiga . "(" . $qtdNormaAntiga . ") -> " . $result['idNorma'] . "(" . $result['qtdNorma'] . ") ";
            $andamentoRepo->save($msg, $idRecebimento, $codProduto, $grade);

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo = $this->_em->getRepository('wms:Enderecamento\Palete');
            $paleteRepo->deletaPaletesRecebidos($idRecebimento, $codProduto, $grade);
            //$this->addFlashMessage('success',"Norma de paletização para o produto $codProduto, grade $grade alterada com sucesso neste recebimento");
            return true;
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            return false;
        }
    }

    /*
     * Método apenas para alocar o endereço sugerido ao palete
     * Só aloca endereço sugerido para os paletes que tiverem :
     * -> todos os volumes conferidos;
     * -> palete que não tenha sido endereçado;
     * -> palete sem endereço sugerido setado;

     * @param $paleteEn = array (
      'IND_IMPRESSO' = Indicativo se ja foi impresso ou não,
      'COD_SIGLA' = Sigla do palete,
      'UMA' = Código do Palete,
      'QTD_VOL_TOTAL' = Quantidade de volumes presentes na norma de paletização do palete (Para produtos volumes),
      'QTD_VOL_CONFERIDO' = Quantidade de volumes no palete (para produtos volume)

     * @param $repositorios = array(
      'enderecoRepo'            => $this->getEntityManager()->getRepository("wms:Deposito\Endereco"),
      'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
      'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
      'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
      'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
      'recebimentoRepo'         => $this->getEntityManager()->getRepository('wms:Recebimento'),
      'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'),
      )

     */

    public function alocaEnderecoAutomaticoPaletes($paletes = array(), $repositorios) {
        foreach ($paletes as $palete) {

            //SÓ TRABALHA NO ENDEREÇAMENTO AUTOMATICO OS PALETES QUE NÂO FORAM IMPRESSOS E NÃO ESTÃO ENDEREÇADOS
            if (($palete['IND_IMPRESSO'] != 'S') &&
                    ($palete['COD_SIGLA'] != Palete::STATUS_ENDERECADO) &&
                    ($palete['COD_SIGLA'] != Palete::STATUS_CANCELADO)) {
                $idUma = $palete['UMA'];

                //SO VAI DAR SUGESTÃO DE ENDEREÇO PARA OS PALETES QUE POSSUEM TODOS OS VOLUMES CONFERIDOS
                if ($palete['QTD_VOL_TOTAL'] == $palete['QTD_VOL_CONFERIDO']) {
                    $paleteEn = $this->findOneBy(array('id' => $idUma));

                    //SÓ FAÇO A SUGESTÃO DE ENDEREÇO PARA PALETES QUE AINDA NÂO TEM ENDEREÇO SUGERIDO
                    if ($paleteEn->getDepositoEndereco() == null) {
                        $sugestaoEndereco = $this->getSugestaoEnderecoPalete($paleteEn, $repositorios);
                        if ($sugestaoEndereco != null) {
                            $idEnderecoSugerido = $sugestaoEndereco['COD_DEPOSITO_ENDERECO'];
                            $this->alocaEnderecoPalete($idUma, $idEnderecoSugerido, $repositorios);
                            $this->getEntityManager()->flush();
                        }
                    }
                }
            }
        }
        return true;
    }

    public function findConferente($idUma, $codProduto, $grade) {
        $sql = "SELECT P.NOM_PESSOA 
                FROM V_QTD_RECEBIMENTO R
                INNER JOIN PALETE P ON R.COD_RECEBIMENTO = P.COD_RECEBIMENTO
                INNER JOIN ORDEM_SERVICO OS ON R.COD_OS = OS.COD_OS
                INNER JOIN PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA
                WHERE UMA = " . $idUma . " AND R.COD_PRODUTO = " . "'$codProduto'" . " AND R.DSC_GRADE = '" . $grade . "'";
        return $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPaletesByStatus($recebimento, $produto, $grade, $volume = null, $lote = null, $status = Palete::STATUS_RECEBIDO)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("pp")
            ->from("wms:Enderecamento\PaleteProduto", "pp")
            ->innerJoin("pp.uma", "p")
            ->where("p.recebimento = $recebimento")
            ->andWhere("pp.codProduto = '$produto'")
            ->andWhere("pp.grade = '$grade'")
            ->andWhere("p.status = $status")
            ->andWhere("p.impresso = 'N'");

        if (!empty($volume)) $dql->andWhere("pp.codProdutoVolume = $volume");
        if (!empty($lote)) $dql->andWhere("pp.lote = '$lote'");

        $dql->orderBy("p.id", "DESC");

        return $dql->getQuery()->getResult();
    }
}
