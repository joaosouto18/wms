<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;
use Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;
use Wms\Module\Web\Grid\Recebimento;

class PaleteRepository extends EntityRepository
{

    public function getQtdProdutosByRecebimento ($params)
    {
        extract($params);

        $query = "
            SELECT DISTINCT R.COD_RECEBIMENTO,
                   TO_CHAR(R.DTH_INICIO_RECEB,'DD/MM/YYYY') as DTH_INICIO_RECEB,
                   TO_CHAR(R.DTH_FINAL_RECEB, 'DD/MM/YYYY') as DTH_FINAL_RECEB,
                   S.DSC_SIGLA,
                   NF.COD_FORNECEDOR,
                   FORNECEDOR.NOM_PESSOA FORNECEDOR,
                   NVL(QTD.QTD,0) as QTD_TOTAL,
                   NVL(PRQ.QTD_ENDERECAMENTO,0) as QTD_ENDERECAMENTO,
                   NVL(PRE.QTD_ENDERECADO,0) as QTD_ENDERECADO,
                   NVL(QTD.QTD,0) - NVL(PRQ.QTD_ENDERECAMENTO,0) - NVL(PRE.QTD_ENDERECADO,0) as QTD_RECEBIMENTO
              FROM RECEBIMENTO R
              LEFT JOIN NOTA_FISCAL NF ON R.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
              LEFT JOIN FORNECEDOR F ON NF.COD_FORNECEDOR = F.COD_FORNECEDOR
              LEFT JOIN (SELECT PJ.COD_PESSOA, P.NOM_PESSOA
                         FROM PESSOA_JURIDICA PJ
                         LEFT JOIN PESSOA P ON PJ.COD_PESSOA = P.COD_PESSOA) FORNECEDOR ON F.COD_FORNECEDOR = FORNECEDOR.COD_PESSOA
              LEFT JOIN (SELECT SUM(QTD) as QTD,
                                COD_RECEBIMENTO
                           FROM V_QTD_RECEBIMENTO
                          GROUP BY COD_RECEBIMENTO) QTD ON QTD.COD_RECEBIMENTO = R.COD_RECEBIMENTO
              LEFT JOIN (SELECT COD_RECEBIMENTO, UMA
                   FROM PALETE
                  WHERE COD_STATUS = 535
                  GROUP BY COD_RECEBIMENTO, UMA) QE ON R.COD_RECEBIMENTO = QE.COD_RECEBIMENTO
              LEFT JOIN (SELECT SUM(QTD) as QTD_ENDERECAMENTO, UMA
                   FROM PALETE_PRODUTO GROUP BY UMA) PRQ ON PRQ.UMA = QE.UMA
              LEFT JOIN (SELECT COD_RECEBIMENTO, UMA
                   FROM PALETE
                  WHERE COD_STATUS = 536
                  GROUP BY COD_RECEBIMENTO, UMA) QF ON R.COD_RECEBIMENTO = QF.COD_RECEBIMENTO
              LEFT JOIN (SELECT SUM(QTD) as QTD_ENDERECADO, UMA
                   FROM PALETE_PRODUTO GROUP BY UMA) PRE ON PRE.UMA = QF.UMA
              LEFT JOIN SIGLA S ON R.COD_STATUS = S.COD_SIGLA
              LEFT JOIN PALETE PA ON R.COD_RECEBIMENTO = PA.COD_RECEBIMENTO
        ";

        $queryWhere = " WHERE ";
        $filter = false;

        if (isset($dataInicial1) && (!empty($dataInicial1))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB >= TO_DATE('$dataInicial1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataInicial2) && (!empty($dataInicial2))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB <= TO_DATE('$dataInicial2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal1) && (!empty($dataFinal1))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB >= TO_DATE('$dataFinal1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal2) && (!empty($dataFinal2))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB <= TO_DATE('$dataFinal2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($status) && (!empty($status))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.COD_STATUS = $status";
            $filter = true;
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.COD_RECEBIMENTO = $idRecebimento";
            $filter = true;
        }

        if (isset($uma) && (!empty($uma))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " PA.UMA = $uma";
            $filter = true;
        }

        if ($filter == true) {$query = $query . $queryWhere . " ORDER BY R.COD_RECEBIMENTO";}

        $array = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $resultadoFinal = array();

        foreach ($array as $recebimento) {
            if (!array_key_exists($recebimento['COD_RECEBIMENTO'], $resultadoFinal)) {
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']] = array();
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['COD_RECEBIMENTO'] = $recebimento['COD_RECEBIMENTO'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['DTH_INICIO_RECEB'] = $recebimento['DTH_INICIO_RECEB'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['DTH_FINAL_RECEB'] = $recebimento['DTH_FINAL_RECEB'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['DSC_SIGLA'] = $recebimento['DSC_SIGLA'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['COD_FORNECEDOR'] = $recebimento['COD_FORNECEDOR'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['FORNECEDOR'] = $recebimento['FORNECEDOR'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_TOTAL'] = $recebimento['QTD_TOTAL'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECAMENTO'] = $recebimento['QTD_ENDERECAMENTO'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECADO'] = $recebimento['QTD_ENDERECADO'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_RECEBIMENTO'] = $recebimento['QTD_RECEBIMENTO'];
            } else {
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_TOTAL'] = $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_TOTAL'] + $recebimento['QTD_TOTAL'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECAMENTO'] = $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECAMENTO'] + $recebimento['QTD_ENDERECAMENTO'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECADO'] = $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_ENDERECADO'] + $recebimento['QTD_ENDERECADO'];
                $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_RECEBIMENTO'] = $resultadoFinal[$recebimento['COD_RECEBIMENTO']]['QTD_RECEBIMENTO'] + $recebimento['QTD_RECEBIMENTO'];
            }
        }

        return $resultadoFinal;

    }

    public function getPaletes ($idRecebimento, $idProduto, $grade, $trowException = true) {

        $this->gerarPaletes($idRecebimento,$idProduto,$grade, $trowException);
        $paletes = $this->getPaletesAndVolumes($idRecebimento,$idProduto,$grade);
        return $paletes;
    }

    public function getQtdEnderecadaByNormaPaletizacao($idRecebimento, $idProduto, $grade,$showVolumes = false) {
        $SQL = "SELECT SUM(QTD.QTD) as QTD, QTD.COD_NORMA_PALETIZACAO
                  FROM (SELECT DISTINCT P.UMA, PP.QTD,PP.COD_NORMA_PALETIZACAO
                          FROM PALETE P
                     LEFT JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                     LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = PP.COD_PRODUTO_VOLUME
                     LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = PP.COD_PRODUTO_EMBALAGEM
                         WHERE ((PE.COD_PRODUTO = '$idProduto' AND PE.DSC_GRADE = '$grade')
                            OR (PV.COD_PRODUTO = '$idProduto' AND PV.DSC_GRADE = '$grade'))
                           AND P.COD_RECEBIMENTO = '$idRecebimento'
                           AND P.COD_STATUS <> ". Palete::STATUS_EM_RECEBIMENTO . ") QTD
                 GROUP BY QTD.COD_NORMA_PALETIZACAO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEmbalagensByOsAndNorma($codOs, $codProduto, $grade, $normaPaletizacao, $codRecebimento){
        $SQL = "
        SELECT DISTINCT
               NULL as COD_PRODUTO_VOLUME,
               RE.COD_PRODUTO_EMBALAGEM
          FROM RECEBIMENTO_EMBALAGEM RE
         INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
          WHERE RE.COD_RECEBIMENTO = '$codRecebimento'
            AND RE.COD_OS = '$codOs'
            AND RE.COD_NORMA_PALETIZACAO = '$normaPaletizacao'
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
            ->leftJoin("wms:Enderecamento\PaleteProduto","pp",'WITH','pp.uma = p.id')
            ->leftJoin("wms:Produto\Embalagem","pe",'WITH','pp.codProdutoEmbalagem = pe.id')
            ->leftJoin("wms:Produto\Volume","pv",'WITH','pp.codProdutoVolume = pv.id')
            ->where("(pv.codProduto = '$idProduto' AND pv.grade = '$grade') OR (pe.codProduto = '$idProduto' AND pe.grade = '$grade')")
            ->andWhere("p.recebimento = $idRecebimento")
            ->andWhere("p.codStatus = ". $codStatus)
            ->groupBy('pp.codNormaPaletizacao, pp.codProdutoEmbalagem, pp.codProdutoVolume ')
            ->orderBy("menorQtd")
            ->distinct(true);
        $result = $query->getQuery()->getArrayResult();

        $qtd = 0;

        $produtoEn     = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$idProduto, 'grade' => $grade));
        if(count($produtoEn->getVolumes()) == 0) {
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

    public function getPaletesAndVolumes ($idRecebimento = null, $idProduto = null, $grade = null, $statusPalete = null, $statusRecebimento = null, $dtInicioRecebimento1 = null, $dtInicioRecebimento2 = null, $dtFinalRecebimento1 = null, $dtFinalRecebimento2 = null, $uma = null) {
        $SQL = " SELECT DISTINCT
                        P.UMA,
                        U.DSC_UNITIZADOR as UNITIZADOR,
                        QTD.QTD,
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
                        NVL(QTD_VOL_CONFERIDO.QTD,1) as QTD_VOL_CONFERIDO
                   FROM PALETE P
                   LEFT JOIN UNITIZADOR U ON P.COD_UNITIZADOR = U.COD_UNITIZADOR
                   LEFT JOIN SIGLA S ON P.COD_STATUS = S.COD_SIGLA
                   LEFT JOIN DEPOSITO_ENDERECO DE ON P.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                   LEFT JOIN RECEBIMENTO R ON R.COD_RECEBIMENTO = P.COD_RECEBIMENTO
                   INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                   INNER JOIN PRODUTO ON PRODUTO.COD_PRODUTO = PP.COD_PRODUTO AND PP.DSC_GRADE = PRODUTO.DSC_GRADE
                   INNER JOIN (SELECT MIN(PP.QTD) as QTD, UMA FROM PALETE_PRODUTO PP GROUP BY UMA) QTD ON QTD.UMA = P.UMA
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
        if (($dtInicioRecebimento1 != NULL) && ($dtInicioRecebimento1 != "")){
            $SQL .= " AND R.DTH_INICIO_RECEB >= TO_DATE('$dtInicioRecebimento1 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (($dtInicioRecebimento2 != NULL) &&($dtInicioRecebimento2 != "")) {
            $SQL .= " AND R.DTH_INICIO_RECEB <= TO_DATE('$dtInicioRecebimento2 23:59','DD-MM-YYYY HH24:MI')";
        }
        if (($dtFinalRecebimento1 != NULL) && ($dtFinalRecebimento1 != "")) {
            $SQL .= " AND R.DTH_FINAL_RECEB >= TO_DATE('$dtFinalRecebimento1 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (($dtFinalRecebimento2 != NULL) && ($dtFinalRecebimento2 != "")) {
            $SQL .= " AND R.DTH_FINAL_RECEB <= TO_DATE('$dtFinalRecebimento2 23:59','DD-MM-YYYY HH24:MI')";
        }
        $SQL .= "   ORDER BY PROD.VOLUMES, P.UMA, S.DSC_SIGLA";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function deletaPaletesEmRecebimento ($idRecebimento, $idProduto, $grade) {

        $ppRepository = $this->_em->getRepository("wms:Enderecamento\PaleteProduto");
        $reservaEstoqueRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoque");
        $reservaEstoqueEnderecamentoRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

        $statusRecebimento = Palete::STATUS_EM_RECEBIMENTO;
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa")
            ->from("wms:Enderecamento\Palete", "pa")
            ->leftJoin("wms:Enderecamento\PaleteProduto", "pp",'WITH','pp.uma = pa.id')
            ->leftJoin("wms:Produto\Embalagem", "pe",'WITH','pe.id = pp.codProdutoEmbalagem')
            ->leftJoin("wms:Produto\Volume", "pv",'WITH','pv.id = pp.codProdutoVolume')
            ->innerJoin("pa.recebimento", "r")
            ->innerJoin("pa.status", "s")
            ->where("r.id = '$idRecebimento'")
            ->andWhere("s.id = '$statusRecebimento'")
            ->andWhere("(pv.codProduto = '$idProduto' AND pv.grade = '$grade') OR (pe.codProduto = '$idProduto' AND pe.grade = '$grade')");
        $paletes = $query->getQuery()->getResult();
        foreach ($paletes as $key => $palete) {
            $produtos = $ppRepository->findBy(array('uma'=>$palete->getId()));
            foreach ($produtos as $produto) {
                $this->getEntityManager()->remove($produto);
            }
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
    public function enderecaPicking ($paletes = array())
    {
        $Resultado = "";
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($paletes == NULL) {
            throw new \Exception("Nenhum Palete Selecionado");
        }

        foreach ($paletes as $palete){
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete")->find($palete);

            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }

            $produtos = $paleteEn->getProdutos();
            if ($produtos) {
                $embalagem   = $produtos[0]->getEmbalagemEn();
                $pickingEn   = $embalagem->getEndereco();
                $codProduto  = $produtos[0]->getCodProduto();
                $grade       = $produtos[0]->getGrade();
                $capacidadePicking = $embalagem->getCapacidadePicking();
                $quantidadePalete = $produtos[0]->getQtd();

                if ($pickingEn == Null) {
                    throw new \Exception("Não existe endereço de picking para o produto " . $embalagem[0]->getCodProduto() . " / " . $embalagem[0]->getGrade());
                }

                $idVolume = null;
                $volumes = array();
                if ($produtos[0]->getCodProdutoVolume() != NULL) {
                    $idVolume = $produtos[0]->getCodProdutoVolume();
                    foreach ($produtos as $volume){
                        $volumes[] = $volume->getCodProdutoVolume();
                    }
                }

                $qtdPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto,$grade,$pickingEn->getId(), $volumes);
                $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto,$grade,$idVolume,$pickingEn->getId(),"E");
                $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto,$grade,$idVolume, $pickingEn->getId(),"S");

                if (($qtdPickingReal + $reservaEntradaPicking + $reservaSaidaPicking + $quantidadePalete) > $capacidadePicking) {
                    $Resultado = "Quantidade nos paletes superior a capacidade do picking";
                }

                $this->alocaEnderecoPalete($paleteEn->getId(),$embalagem->getEndereco()->getId());
            }
            $this->getEntityManager()->flush();
        }
        return $Resultado;
    }

    public function deletaPaletesRecebidos ($idRecebimento, $idProduto, $grade) {
        $ppRepository = $this->_em->getRepository("wms:Enderecamento\PaleteProduto");

        $statusRecebimento = Palete::STATUS_RECEBIDO;
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa")
            ->from("wms:Enderecamento\Palete", "pa")
            ->leftJoin("wms:Enderecamento\PaleteProduto", "pp",'WITH','pp.uma = pa.id')
            ->leftJoin("wms:Produto\Embalagem", "pe",'WITH','pe.id = pp.codProdutoEmbalagem')
            ->leftJoin("wms:Produto\Volume", "pv",'WITH','pv.id = pp.codProdutoVolume')
            ->innerJoin("pa.recebimento", "r")
            ->innerJoin("pa.status", "s")
            ->where("r.id = '$idRecebimento'")
            ->andWhere("s.id = '$statusRecebimento'")
            ->andWhere("(pv.codProduto = '$idProduto' AND pv.grade = '$grade') OR (pe.codProduto = '$idProduto' AND pe.grade = '$grade')");
        $paletes = $query->getQuery()->getResult();
        foreach ($paletes as $key => $palete) {
            $produtos = $ppRepository->findBy(array('uma'=>$palete->getId()));
            foreach ($produtos as $produto) {
                $this->getEntityManager()->remove($produto);
            }
            $this->getEntityManager()->remove($palete);
        }
        $this->_em->flush();
    }


    public function getQtdEmRecebimento ($idRecebimento, $idProduto, $grade) {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo    = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');

        $produtoEn     = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$idProduto, 'grade' => $grade));

        $qtdTotalReceb = $conferenciaRepo->getQtdByRecebimento($idRecebimento,$idProduto,$grade);
        $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($idRecebimento,$idProduto,$grade);

        $qtdTotalEnd = 0;
        if(count($produtoEn->getVolumes()) == 0) {
            foreach ($qtdEnderecada as  $enderecado) {
                $qtdTotalEnd = $qtdTotalEnd + $enderecado['QTD'];
            }
        } else {
            if (count($qtdEnderecada) >0) {
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

    public function gerarPaletes ($idRecebimento, $idProduto, $grade, $throwException = true)
    {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo    = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');

        $recebimentoEn = $this->getEntityManager()->getRepository('wms:Recebimento')->find($idRecebimento);
        $produtoEn     = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$idProduto, 'grade' => $grade));

        if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO) {
            $codStatus = Palete::STATUS_RECEBIDO;
            $recebimentoFinalizado = true;
        } else if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_DESFEITO){
            $codStatus = Palete::STATUS_CANCELADO;
            $recebimentoFinalizado = true;
        } else if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_CANCELADO){
            $codStatus = Palete::STATUS_CANCELADO;
            $recebimentoFinalizado = true;
        } else {
            $codStatus = Palete::STATUS_EM_RECEBIMENTO;
            $recebimentoFinalizado = false;
        }
        $statusEn      = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find($codStatus);

        $qtdEnderecada = $this->getQtdEnderecadaByNormaPaletizacao($recebimentoEn->getId(),$idProduto,$grade);
        if(count($produtoEn->getVolumes()) == 0) {
            $tipo = "E";
            $idOs = $conferenciaRepo->getLastOsRecebimentoEmbalagem($idRecebimento,$idProduto,$grade);
            $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoEmbalagemAndNorma($idOs, $idProduto, $grade);
        } else {
            $tipo = "V";
            $idOs = $conferenciaRepo->getLastOsRecebimentoVolume($idRecebimento,$idProduto,$grade);
            $qtdRecebida = $conferenciaRepo->getQtdByRecebimentoVolumeAndNorma($idOs, $idProduto, $grade);
        }

        if (count($qtdRecebida) <= 0) {
            if ($throwException == true) {
                throw new Exception("O recebimento do produto $idProduto não possui unitizador ou ainda não foi conferido");
            }
        }

        foreach ($qtdEnderecada as $enderecado) {
            foreach ($qtdRecebida as $key => $recebido) {
                if ($recebido['COD_NORMA_PALETIZACAO'] == $enderecado['COD_NORMA_PALETIZACAO']){
                    $qtdRecebida[$key]['QTD'] = $recebido['QTD'] - $enderecado['QTD'];
                }
            }
        }

        $this->deletaPaletesEmRecebimento($recebimentoEn->getId(),$idProduto,$grade);
        $qtdLimite = null;
        if ($recebimentoFinalizado == false) {
            $qtdLimite = $this->getQtdLimiteRecebimento($recebimentoEn->getId(),$idProduto,$grade,$qtdRecebida,$qtdEnderecada, $tipo);
        }

        $this->salvaNovosPaletes($produtoEn, $qtdRecebida,$idProduto,$idOs,$grade,$recebimentoFinalizado,$qtdLimite,$tipo, $recebimentoEn, $statusEn);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function getQtdLimiteRecebimento($codRecebimento, $codProduto, $grade, $qtdRecebida, $qtdEnderecada, $tipo){
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $nfRepo */
        $nfRepo    = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $qtdLimiteTotal = $nfRepo->getQtdByProduto($codRecebimento,$codProduto,$grade);
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

    public function salvaNovosPaletes($produtoEn, $qtdRecebida, $idProduto, $idOs, $grade, $recebimentoFinalizado, $qtdLimite, $tipo, $recebimentoEn, $statusEn){
        foreach ($qtdRecebida as $unitizador) {
            $idNorma = $unitizador['COD_NORMA_PALETIZACAO'];
            if ($unitizador['QTD'] > 0) {
                if ($unitizador['NUM_NORMA'] == 0) {
                    throw new Exception("O produto $idProduto não possui norma de paletização");
                }

                if ($tipo == "V") {
                    $volumes = $this->getVolumesByOsAndNorma($idOs,$idProduto,$grade,$idNorma, $recebimentoEn->getId());
                } else {
                    $volumes = $this->getEmbalagensByOsAndNorma($idOs,$idProduto,$grade,$idNorma, $recebimentoEn->getId());
                }
                $qtd = $unitizador['QTD'];

                /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
                $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
                $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($recebimentoEn->getId(), null, $idProduto, $grade);

                if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto)) {
                    $dataValidade = $getDataValidadeUltimoProduto['dataValidade'];
                } else {
                    $dataValidade = null;
                }

                //TRAVA PARA GERAR NO MAXIMO A QUANTIDADE TOTAL DA NOTA ENQUANTO O RECEBIMENTO NÃO TIVER SIDO FINALIZADO
                if ($recebimentoFinalizado == false) {
                    if ($tipo == "V"){
                        $qtdLimite[$idNorma] = $qtdLimite[$idNorma] - $qtd;
                        if ($qtdLimite[$idNorma] < 0) {
                            $qtd = $qtd + $qtdLimite[$idNorma];
                        }
                    } else {
                        $qtdLimite = $qtdLimite - $qtd;
                        if ($qtdLimite < 0) {
                            $qtd = $qtd + $qtdLimite;
                        }
                    }
                }

                $qtdPaletes         = $qtd / $unitizador['NUM_NORMA'];
                $qtdUltimoPalete    = $qtd % $unitizador['NUM_NORMA'];
                $unitizadorEn       = $this->getEntityManager()->getRepository('wms:Armazenagem\Unitizador')->find($unitizador['COD_UNITIZADOR']);

                for ($i = 1; $i <= $qtdPaletes; $i++) {
                    $this->salvarPaleteEntity($produtoEn, $recebimentoEn,$unitizadorEn,$statusEn,$volumes,$idNorma,$unitizador['NUM_NORMA'], $dataValidade);
                }

                if ($qtdUltimoPalete > 0) {
                    //TRAVA PARA GERAR O PALETE COM A QUANTIDADE QUEBRADA SOMENTE SE TIVER FINALIZADO
                    if ($recebimentoFinalizado == true) {
                        $this->salvarPaleteEntity($produtoEn, $recebimentoEn,$unitizadorEn,$statusEn,$volumes,$idNorma,$qtdUltimoPalete, $dataValidade);
                    }
                }
            }
        }
    }

    public function salvarPaleteEntity($produtoEn, $recebimentoEn,$unitizadorEn,$statusEn,$volumes,$idNorma,$Qtd,$dataValidade){
        $dataValidade = new \DateTime($dataValidade);
        $paleteEn = new Palete();
        $paleteEn->setRecebimento($recebimentoEn);
        $paleteEn->setUnitizador($unitizadorEn);
        $paleteEn->setStatus($statusEn);
        $paleteEn->setImpresso('N');
        $paleteEn->setDepositoEndereco(null);
        $this->_em->persist($paleteEn);
        foreach ($volumes as $volume) {
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


    public function finalizar(array $paletes, $idPessoa, $formaConferencia = OrdemServicoEntity::MANUAL, $dataValidade = null)
    {
        if (count($paletes) <= 0 || empty($idPessoa)) {
            throw new Exception('Usuario ou palete não informados');
        }
        $retorno = array();
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if (isset($dataValidade) and !is_null($dataValidade)) {
            $validade = new \DateTime($dataValidade['dataValidade']);
        }
        $ok = false;
        foreach($paletes as $paleteId) {
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->find($paleteId);
            if ($paleteEn->getCodStatus() != Palete::STATUS_ENDERECADO && $paleteEn->getCodStatus() != Palete::STATUS_CANCELADO) {
                if ($formaConferencia == OrdemServicoEntity::COLETOR) {
                    $paleteEn->setCodStatus(Palete::STATUS_ENDERECADO);
                    if (isset($validade) && !empty($validade)) {
                        $paleteEn->setValidade($validade);
                    }
                    $this->_em->persist($paleteEn);
                    $retorno = $this->criarOrdemServico($paleteId, $idPessoa, $formaConferencia);
                } else {
                    if ($paleteEn->getCodStatus() == Palete::STATUS_EM_ENDERECAMENTO) {
                        $paleteEn->setCodStatus(Palete::STATUS_ENDERECADO);
                        $paleteEn->setValidade($validade);
                        $this->_em->persist($paleteEn);
                        $retorno = $this->criarOrdemServico($paleteId, $idPessoa, $formaConferencia);
                    }
                }
                if ($retorno['criado']) {
                    $ok = true;
                    $this->getEntityManager()->flush();
                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                        $idEstoque = $paleteEn->getDepositoEndereco()->getId();
                        $produtosArray = $paleteEn->getProdutosArray();
                        $idPalete = $paleteEn->getId();
                        $idUnitizador = $paleteEn->getUnitizador()->getId();
                        $this->getEntityManager()->clear();
                        $reservaEstoqueRepo->efetivaReservaEstoque($idEstoque,$produtosArray,"E","U",$idPalete,$idPessoa,$retorno['id'],$idUnitizador,null,$dataValidade);
                    }
                }
            }
        }

        $this->_em->flush();
        return $ok;
    }

    public function criarOrdemServico($idEnderecamento, $idPessoa, $formaConferencia)
    {
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

    public function alocaEnderecoPaleteByBlocado($idPalete, $idEndereco)
    {
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

    public function alocaEnderecoPalete($idPalete, $idEndereco) {

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");

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

        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoNovoEn = $enderecoRepo->find($idEndereco);
        $enderecoAntigoEn = $paleteEn->getDepositoEndereco();

        $arrayProdutos = $paleteEn->getProdutosArray();

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($enderecoAntigoEn != NULL) {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigoEn,$qtdAdjacente,"LIBERAR");
            $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$arrayProdutos,"E","U",$paleteEn->getId());
            if ($enderecoAntigoEn->getId() != $enderecoNovoEn->getId()) {
                $paleteEn->setImpresso("N");
            }
        } else {
            $paleteEn->setImpresso("N");
        }
        $paleteEn->setDepositoEndereco($enderecoNovoEn);
        $paleteEn->setCodStatus($paleteEn::STATUS_EM_ENDERECAMENTO);
        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoNovoEn,$qtdAdjacente,"OCUPAR");
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoNovoEn->getId(),$arrayProdutos,"E","U",$paleteEn->getId());

        $this->getEntityManager()->persist($paleteEn);
    }

    public function getPaletesReport($values)
    {
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
            ->innerJoin("pa.produtos","pp")
            ->innerJoin("pp.produto", "prod")
            ->innerJoin("pa.recebimento", "r")
            ->innerJoin("r.status", "s")
            ->leftJoin("pa.depositoEndereco", "dep");

        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2)))
        {
            $dataInicial1 = str_replace("/", "-", $dataInicial1);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $dataInicial2);
            $dataI2 = new \DateTime($dataInicial2);

            $query->where("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2);
        }

        if (isset($dataFinal1) && (!empty($dataFinal1)) && (!empty($dataFinal2)))
        {
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

    public function cancelaPalete($idUma) {
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->findOneBy(array('id'=> $idUma ));

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

        $idUsuarioLogado  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if ($paleteEn == NULL) {
            throw new \Exception ("Palete não encontrado");
        }

        try {
            if ($paleteEn->getCodStatus() == \Wms\Domain\Entity\Enderecamento\Palete::STATUS_ENDERECADO) {

                $enderecoEn = $paleteEn->getDepositoEndereco();
                $idUma = $paleteEn->getId();
                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
                $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");

                $params = array();
                foreach($paleteEn->getProdutos() as $produto){
                    $params['produto'] = $produto->getProduto();
                    $params['endereco'] = $enderecoEn;
                    $params['qtd'] = $produto->getQtd() * -1;
                    $params['observacoes'] = "Mov. ref. cancelamento do Palete ". $idUma;

                    if ($produto->getCodProdutoEmbalagem()) {
                        $params['embalagem'] = $embalagemRepo->findOneBy(array('id'=>$produto->getCodProdutoEmbalagem())) ;
                    } else {
                        $params['volume'] = $volumeRepo->findOneBy(array('id'=>$produto->getCodProdutoVolume())) ;
                    }

                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO){
                        $estoqueRepo->movimentaEstoque($params);
                    }
                }
            }

            $paleteEn->setCodStatus(Palete::STATUS_CANCELADO);

            $this->getEntityManager()->persist($paleteEn);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
            throw new \Exception ($e->getMessage());
        }
    }

    public function desfazerPalete($idUma) {

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->findOneBy(array('id'=> $idUma ));

        if ($paleteEn == NULL) {
            throw new \Exception ("Palete $idUma não encontrado");
        }

        $idUma = $paleteEn->getId();
        try{
            switch ($paleteEn->getCodStatus()){
                case Palete::STATUS_ENDERECADO:
                    $idEndereco = $paleteEn->getDepositoEndereco()->getId();

                    $reservaEstoqueRepo->reabrirReservaEstoque($idEndereco,$paleteEn->getProdutosArray(),"E","U",$idUma);
                    $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_ENDERECAMENTO);
                    $this->getEntityManager()->persist($paleteEn);

                    $ordensServicoEn = $this->getEntityManager()->getRepository('wms:OrdemServico')->findBy(array('idEnderecamento'=>$paleteEn->getId()));
                    foreach ($ordensServicoEn as $osEn) {
                        if ($osEn->getDscObservacao() == NULL) {
                            $osEn->setDscObservacao('Endereçamento desfeito');
                            $this->getEntityManager()->persist($osEn);
                        }
                    }
                    break;
                case Palete::STATUS_EM_ENDERECAMENTO:
                    $idEndereco = $paleteEn->getDepositoEndereco()->getId();

                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                        $codStatus = \Wms\Domain\Entity\Enderecamento\Palete::STATUS_RECEBIDO;
                    } else {
                        $codStatus = \Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO;
                    }

                    $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
                    $enderecoAntigo = $paleteEn->getDepositoEndereco();
                    if ($enderecoAntigo != NULL) {
                        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
                        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                        $reservaEstoqueRepo->cancelaReservaEstoque($idEndereco,$paleteEn->getProdutosArray(),"E","U",$idUma);
                    }

                    $paleteEn->setDepositoEndereco(NULL);
                    $paleteEn->setImpresso("N");
                    $paleteEn->setCodStatus($codStatus);
                    $this->getEntityManager()->persist($paleteEn);
                    break;
                case Palete::STATUS_RECEBIDO:
                case Palete::STATUS_EM_RECEBIMENTO:
                    $this->getEntityManager()->remove($paleteEn);
                    break;
            }
            $this->getEntityManager()->flush();
        } catch(Exception $e) {
            throw new \Exception ($e->getMessage());
        }
        return true;
    }

    public function getImprimeNorma($idRecebimento, $idProduto, $grade)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("u.descricao")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin("pa.produtos","pp")
            ->innerJoin("pa.unitizador", "u")
            ->innerJoin("pp.produto", "prod")
            ->innerJoin("pa.recebimento", "r")
            ->where("r.id = $idRecebimento")
            ->andWhere("prod.id = $idProduto")
            ->andWhere("prod.grade = '$grade'");

        $array = $query->getQuery()->getArrayResult();

        $norma = $array[0]['descricao'];
        return $norma;

    }

    public function getByRecebimentoAndStatus($recebimento, $status = Palete::STATUS_CANCELADO)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa.id, u.descricao unitizador, pa.qtd, sigla.sigla status, de.descricao endereco, pa.impresso")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin('pa.unitizador', 'u')
            ->innerJoin('pa.status', 'sigla')
            ->leftJoin('pa.depositoEndereco', 'de')
            ->where("pa.status = ".$status);

        if ($recebimento) {
            $query->andWhere('pa.recebimento = :recebimento')
                ->setParameter('recebimento', $recebimento);
        }

        return $query->getQuery()->getArrayResult();
    }

    public function realizaTroca($recebimento, array $umas)
    {
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->find($idUsuario);

        foreach($umas as $uma)
        {
            $entity = $this->find($uma);
            $entRecebimento = $this->_em->getReference('wms:Recebimento', $recebimento);
            $entity->setStatus($entity->getStatus());
            $entity->setRecebimento($entRecebimento);

            if ($entRecebimento->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO) {
                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

                $reservaEstoqueEnderecamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");
                $reservaEstoque = $reservaEstoqueEnderecamentoRepo->findOneBy(array('palete'=> $uma));

                $reservaEstoqueEn = $reservaEstoque->getReservaEstoque();
                if ($reservaEstoqueEn->getAtendida() == 'N') {
                    $reservaEstoqueRepo->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn,"E",$uma,$usuarioEn);
                }

            }

            $this->_em->persist($entity);
        }
        try {
            $this->_em->flush();
            return true;
        } catch(Exception $e) {
            throw new $e->getMessage();
        }
    }

    public function getPaletesByProdutoAndGrade($params)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa.id, u.descricao unitizador, pp.qtd, sigla.sigla status, de.descricao endereco, pa.impresso")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin('pa.unitizador', 'u')
            ->innerJoin('pa.recebimento', 'receb')
            ->innerJoin('pa.status', 'sigla')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = pa.id')
            ->leftJoin('pa.depositoEndereco', 'de')
            ->setParameter('recebimento', $params['id'])
            ->setParameter('produto', $params['codigo'])
            ->andWhere('pp.codProduto = :produto')
            ->andWhere('pa.recebimento = :recebimento')
            ->distinct(true);

        return $query->getQuery()->getResult();
    }

    public function getSugestaoEnderecoByProdutoAndRecebimento ($codProduto, $dscGrade, $codRecebimento, $tamanhoPalete) {

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');
        $modeloEnderecamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo');

        $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto, 'grade'=>$dscGrade));

        $endAreaArmazenagem = $produtoRepo->getSequenciaEndAutomaticoAreaArmazenagem($codProduto,$dscGrade,true);
        $endTipoEstrutura   = $produtoRepo->getSequenciaEndAutomaticoTpEstrutura($codProduto,$dscGrade,true);
        $endTipoEndereco    = $produtoRepo->getSequenciaEndAutomaticoTpEndereco($codProduto,$dscGrade,true);

        $codModelo = $this->getSystemParameterValue('MODELO_ENDERECAMENTO_PADRAO');
        $recebimentoEn = $recebimentoRepo->findOneBy(array('id'=>$codRecebimento));
        $modeloEnderecamento = $recebimentoEn->getModeloEnderecamento();

        if ($modeloEnderecamento != null) {
            $codModelo= $modeloEnderecamento->getId();
        }

        $enderecoReferencia = $produtoEn->getEnderecoReferencia();
        if ($enderecoReferencia == null) {
            $modeloEnderecamento = $modeloEnderecamentoRepo->findOneBy(array('id'=>$codModelo));
            $enderecoReferencia = $modeloEnderecamento->getCodReferencia();
        }


        if ($enderecoReferencia != null) {
            $ruaReferencia = $enderecoReferencia->getRua();
            $predioReferencia = $enderecoReferencia->getPredio();
            $nivelReferencia = $enderecoReferencia->getNivel();
            $apartamentoReferencia = $enderecoReferencia->getApartamento();
        } else {
            return null;
        }

        if (count($endAreaArmazenagem) >0) {
            $sqlArea = " INNER JOIN PRODUTO_END_AREA_ARMAZENAGEM AA
                            ON AA.COD_PRODUTO = '$codProduto' AND AA.DSC_GRADE = '$dscGrade'
                           AND AA.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM";
        } else {
            $sqlArea = "INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE , COD_AREA_ARMAZENAGEM
                                      FROM MODELO_END_AREA_ARMAZ
                                     WHERE COD_MODELO_ENDERECAMENTO = $codModelo) AA
                                ON AA.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM";
        }

        if (count($endTipoEndereco)>0) {
            $sqlTipoEndereco = " INNER JOIN PRODUTO_END_TIPO_ENDERECO TE
                                    ON TE.COD_PRODUTO = '$codProduto' AND TE.DSC_GRADE = '$dscGrade'
                                   AND TE.COD_TIPO_ENDERECO = DE.COD_TIPO_ENDERECO";
        } else {
            $sqlTipoEndereco = "INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE, COD_TIPO_ENDERECO
                                      FROM MODELO_END_TIPO_ENDERECO
                                     WHERE COD_MODELO_ENDERECAMENTO = $codModelo) TE
                                ON AA.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM";
        }

        if (count($endTipoEstrutura)>0){
            $sqlTipoEstrutura = " INNER JOIN PRODUTO_END_TIPO_EST_ARMAZ ET
                                     ON ET.COD_PRODUTO = '$codProduto' AND ET.DSC_GRADE = '$dscGrade'
                                    AND ET.COD_TIPO_EST_ARMAZ = DE.COD_TIPO_EST_ARMAZ";
        } else{
            $sqlTipoEstrutura = " INNER JOIN (SELECT COD_PRIORIDADE AS NUM_PRIORIDADE, COD_TIPO_EST_ARMAZ
                                                FROM MODELO_END_EST_ARMAZ
                                               WHERE COD_MODELO_ENDERECAMENTO = $codModelo) ET
                                          ON ET.COD_TIPO_EST_ARMAZ = DE.COD_TIPO_EST_ARMAZ";
        }

        $SQL = " SELECT DE.COD_DEPOSITO_ENDERECO,
                        DE.DSC_DEPOSITO_ENDERECO,
                        ABS(DE.NUM_RUA - $ruaReferencia) as DIF_RUA,
                        ABS(DE.NUM_PREDIO - $predioReferencia) as DIF_PREDIO,
                        ABS(DE.NUM_NIVEL - $nivelReferencia) as DIF_NIVEL,
                        ABS(DE.NUM_APARTAMENTO - $apartamentoReferencia) as DIF_APARTAMENTO
                   FROM DEPOSITO_ENDERECO DE
                  INNER JOIN V_OCUPACAO_LONGARINA LONGARINA
                     ON LONGARINA.NUM_PREDIO  = DE.NUM_PREDIO
                    AND LONGARINA.NUM_NIVEL   = DE.NUM_NIVEL
                    AND LONGARINA.NUM_RUA     = DE.NUM_RUA
                  $sqlArea
                  $sqlTipoEndereco
                  $sqlTipoEstrutura
                  WHERE DE.IND_ATIVO = 'S'
                    AND ((DE.COD_CARACTERISTICA_ENDERECO  != 37) OR (DE.COD_TIPO_EST_ARMAZ = 26))
                    AND ((LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO) >= $tamanhoPalete)
                    AND DE.IND_DISPONIVEL = 'S'
                    AND ROWNUM = 1
               ORDER BY ET.NUM_PRIORIDADE, AA.NUM_PRIORIDADE, TE.NUM_PRIORIDADE, DIF_RUA,DIF_PREDIO,DIF_NIVEL,DIF_APARTAMENTO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result)>0) {
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
            $this->addFlashMessage('error',"O Produto $codProduto, grade $grade não possuí norma de paletização");
            return false;
        }

        /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimento $recebimentoEn */
        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento\VQtdRecebimento")->findOneBy(array('codRecebimento' => $idRecebimento, 'codProduto'=>$codProduto, 'grade'=>$grade));
        $conferenciaEn = $conferenciaRepo->findOneBy(array('recebimento'=> $idRecebimento,'codProduto'=>$codProduto,'grade'=>$grade));

        if (($recebimentoEn == NULL) && ($conferenciaEn == NULL)){
            $this->addFlashMessage('error',"Nenhuma quantidade conferida para o produto $codProduto, grade $grade");
            return false;
        }

        try {
            if ($recebimentoEn == null) {
                $idOs = $conferenciaRepo->getLastOsConferencia($idRecebimento,$codProduto,$grade);
                $idNormaAntiga = 'Nenhuma Norma';
                $qtdNormaAntiga = 0;
            } else {
                $normaAntigaEn = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id'=>$recebimentoEn->getCodNormaPaletizacao()));
                if ($normaAntigaEn == null) {
                    $idNormaAntiga = "";
                    $qtdNormaAntiga = "SEM NORMA ANTIGA";
                } else {
                    $idNormaAntiga = $normaAntigaEn->getId();
                    $qtdNormaAntiga = $normaAntigaEn->getNumNorma();
                }

                $idOs = $recebimentoEn->getCodOs();
            }

            $recebimentoRepo->alteraNormaPaletizacaoRecebimento($idRecebimento,$codProduto,$grade,$idOs, $idNorma);

            /** @var \Wms\Domain\Entity\Enderecamento\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Enderecamento\Andamento');
            $msg = "Norma de paletização trocada com sucesso para a da unidade " . $result['unidade'] ." (" . $result['unitizador'] . ")  | Norma: ". $idNormaAntiga . "(" .  $qtdNormaAntiga . ") -> " . $result['idNorma'] . "(" . $result['qtdNorma'] . ") ";
            $andamentoRepo->save($msg, $idRecebimento, $codProduto, $grade);

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo  = $this->_em->getRepository('wms:Enderecamento\Palete');
            $paleteRepo->deletaPaletesRecebidos($idRecebimento,$codProduto, $grade);
            //$this->addFlashMessage('success',"Norma de paletização para o produto $codProduto, grade $grade alterada com sucesso neste recebimento");
            return true;
        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            return false;
        }

    }

}
