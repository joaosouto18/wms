<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao as Etiqueta;
use Wms\Domain\Entity\Produto\Embalagem;
use Wms\Domain\Entity\Produto\EmbalagemRepository;
use Wms\Domain\Entity\Produto\Lote;
use Wms\Domain\Entity\Produto\Volume;
use Wms\Domain\Entity\ProdutoRepository;
use Wms\Math;

class MapaSeparacaoRepository extends EntityRepository {

    protected $math;

    public function getDetalhesConferenciaMapaProduto($idMapa, $idProduto, $grade, $numConferencia) {
        $SQL = "SELECT OS.COD_OS,
                       P.NOM_PESSOA,
                       NVL(PV.DSC_VOLUME, PE.DSC_EMBALAGEM || ' (' || MSC.QTD_EMBALAGEM || ')') as EMBALAGEM,
                       MSC.QTD_CONFERIDA,
                       TO_CHAR(DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') as DTH_CONFERENCIA,
                       COD_MAPA_SEPARACAO_EMBALADO
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSC.COD_PRODUTO_VOLUME
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSC.COD_PRODUTO_EMBALAGEM
                  LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = MSC.COD_OS
                  LEFT JOIN PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA
                 WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                   AND MSC.COD_PRODUTO = '$idProduto'
                   AND MSC.DSC_GRADE = '$grade'
                   AND MSC.NUM_CONFERENCIA = $numConferencia
                 ORDER BY MSC.DTH_CONFERENCIA";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getResumoConferenciaMapaProduto($idMapa) {
        $SQL = "SELECT MSP.COD_MAPA_SEPARACAO,
                       P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       NVL(CONF.NUM_CONFERENCIA,0) as NUM_CONFERENCIA,
                       SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR) as QTD_SEPARAR,
                       SUM(MSP.QTD_CORTADO) as QTD_CORTADO,
                       NVL(CONF.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                       NVL(SMS.TOTAL_SEPARADO,0) as QTD_SEPARADA,
                       CASE WHEN ((CONF.QTD_CONFERIDA <> 0 AND (CONF.QTD_CONFERIDA + SUM(MSP.QTD_CORTADO)) = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR)) OR (CONF.QTD_CONFERIDA <> 0 AND (CONF.QTD_CONFERIDA = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR)))) THEN 'CONFERIDO'
                            WHEN (SUM(MSP.QTD_CORTADO) = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR)) THEN 'CORTADO'
                            ELSE 'PENDENTE'
                       END AS CONFERIDO
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  LEFT JOIN (SELECT SUM(QTD_SEPARADA * QTD_EMBALAGEM) AS TOTAL_SEPARADO, COD_PRODUTO, DSC_GRADE, COD_MAPA_SEPARACAO
                            FROM  SEPARACAO_MAPA_SEPARACAO  GROUP BY COD_PRODUTO, DSC_GRADE, COD_MAPA_SEPARACAO) SMS ON SMS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO AND
                            SMS.COD_PRODUTO = MSP.COD_PRODUTO AND SMS.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN (SELECT MSC.NUM_CONFERENCIA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, SUM (MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) as QTD_CONFERIDA
                               FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              INNER JOIN (SELECT MAX(NUM_CONFERENCIA) MAX_CONFERENCIA, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) VOLUME, COD_MAPA_SEPARACAO
                                            FROM MAPA_SEPARACAO_CONFERENCIA
                                           GROUP BY COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0), COD_MAPA_SEPARACAO) MAX_MSC
                                 ON MAX_MSC.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO
                                AND MAX_MSC.COD_PRODUTO = MSC.COD_PRODUTO
                                AND MAX_MSC.DSC_GRADE = MSC.DSC_GRADE
                                AND MAX_MSC.MAX_CONFERENCIA = MSC.NUM_CONFERENCIA
                              GROUP BY MSC.NUM_CONFERENCIA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO) CONF
                    ON CONF.COD_PRODUTO = MSP.COD_PRODUTO
                   AND CONF.DSC_GRADE = MSP.DSC_GRADE
                   AND CONF.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                 WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                 GROUP BY P.COD_PRODUTO,
                          P.DSC_GRADE,
                          P.DSC_PRODUTO,
                        MSP.COD_MAPA_SEPARACAO,
                       CONF.NUM_CONFERENCIA,
                       CONF.QTD_CONFERIDA,
                        MSP.IND_CONFERIDO,
                        TOTAL_SEPARADO
                 ORDER BY P.DSC_PRODUTO,
                        P.DSC_GRADE
                          ";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($result) && is_array($result)) {
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            foreach ($result as $key => $value) {
                if ($value['QTD_SEPARAR'] > 0) {
                    $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD_SEPARAR']);
                    $result[$key]['QTD_SEPARAR'] = implode('<br />', $vetSeparar);
                }
                if ($value['QTD_CORTADO'] > 0) {
                    $vetCortado = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD_CORTADO']);
                    $result[$key]['QTD_CORTADO'] = implode('<br />', $vetCortado);
                }
                if ($value['QTD_CONFERIDA'] > 0) {
                    $vetConferida = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD_CONFERIDA']);
                    $result[$key]['QTD_CONFERIDA'] = implode('<br />', $vetConferida);
                }
                if ($value['QTD_SEPARADA'] > 0) {
                    $vetConferida = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD_SEPARADA']);
                    $result[$key]['QTD_SEPARADA'] = implode('<br />', $vetConferida);
                }
            }
        }
        return $result;
    }

    public function getResumoConferenciaMapaByExpedicao($idExpedicao) {
        $SQL = "SELECT MS.COD_MAPA_SEPARACAO, MS.DTH_CRIACAO, TRIM(MS.DSC_QUEBRA) as QUEBRA, MSP.QTD_SEPARAR as QTD_TOTAL, NVL(MSC.QTD_CONF,0) as QTD_CONF,
                     CAST(CASE WHEN MSP.QTD_SEPARAR = 0 THEN 0 ELSE (MSC.QTD_CONF/MSP.QTD_SEPARAR) END * 100 as NUMBER(6,2)) || '%' as PERCENTUAL,
                     CAST(CASE WHEN MSP.QTD_SEPARAR = 0 THEN 0 ELSE (SMS.TOTAL_SEPARADO/MSP.QTD_SEPARAR) END * 100 as NUMBER(6,2)) || '%' as PERCENTUAL_SEPARACAO,
                     MS.COD_EXPEDICAO
                FROM MAPA_SEPARACAO MS
                LEFT JOIN (SELECT MSP.COD_MAPA_SEPARACAO, SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM)- MSP.QTD_CORTADO) as QTD_SEPARAR
                             FROM MAPA_SEPARACAO MS
                            INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                            GROUP BY MSP.COD_MAPA_SEPARACAO) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (SELECT COD_MAPA_SEPARACAO, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                             FROM MAPA_SEPARACAO_CONFERENCIA GROUP BY COD_MAPA_SEPARACAO) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (SELECT SUM(QTD_SEPARADA * QTD_EMBALAGEM) AS TOTAL_SEPARADO, COD_MAPA_SEPARACAO
                            FROM  SEPARACAO_MAPA_SEPARACAO  GROUP BY  COD_MAPA_SEPARACAO) SMS ON SMS.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_EXPEDICAO = $idExpedicao
                ORDER BY MS.COD_MAPA_SEPARACAO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getSaldoConfConsolidado($idPedidoProduto, $codProduto, $grade, $mapa, $idCliente)
    {

        $SQL = "SELECT ((MSP.QTD - NVL(MSP.QTD_CORTADA,0)) - NVL(MSC.QTD_CONF,0)) as SALDO
                FROM MAPA_SEPARACAO_PEDIDO MSP
                LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                             FROM MAPA_SEPARACAO_CONFERENCIA
                            WHERE COD_PRODUTO = '$codProduto' AND DSC_GRADE = '$grade' AND COD_PESSOA = $idCliente
                            GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSC ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                WHERE  MSP.COD_PEDIDO_PRODUTO = $idPedidoProduto AND MSP.COD_MAPA_SEPARACAO = $mapa";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['SALDO'];
    }

    public function getSaldoConfComum($idExpedicao, $codProduto, $grade, $idMapa)
    {

        $SQL = "SELECT (MSP.QTD_SEPARAR - NVL(MSC.QTD_CONF,0)) as SALDO
                FROM MAPA_SEPARACAO MS
                INNER JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, SUM((QTD_SEPARAR * QTD_EMBALAGEM)- QTD_CORTADO) as QTD_SEPARAR
                             FROM MAPA_SEPARACAO_PRODUTO
                            WHERE COD_PRODUTO = '$codProduto' AND DSC_GRADE = '$grade'
                            GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                             FROM MAPA_SEPARACAO_CONFERENCIA 
                            WHERE COD_PRODUTO = '$codProduto' AND DSC_GRADE = '$grade'
                            GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_EXPEDICAO = $idExpedicao AND MS.COD_MAPA_SEPARACAO = $idMapa";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['SALDO'];
    }

    /*
      public function verificaMapaSeparacao($expedicaoEn, $idMapa){
      $mapaSeparacaoRepo  = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
      $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');

      $conferenciaFinalizada = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'A');
      $this->alteraStatusMapaAndMapaProdutos($expedicaoEn,$idMapa);

      if ($this->getSystemParameterValue('RESETA_CONFERENCIA_MAPA') == 'S') {
      $this->fechaConferencia($expedicaoEn, $idMapa);
      }

      if (count($conferenciaFinalizada) > 0) {
      $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('expedicao' => $expedicaoEn));
      foreach ($mapaSeparacaoEn as $mapaSeparacao) {
      $mapaSeparacaoProdutos = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaSeparacao->getId()));
      foreach ($mapaSeparacaoProdutos as $mapaProduto) {
      $mapaProduto->setDivergencia('N');
      $this->getEntityManager()->persist($mapaProduto);
      }
      }

      foreach ($conferenciaFinalizada as $mapaSeparacaoProduto) {
      $mapaSeparacaoProdutoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacaoProduto', (int)$mapaSeparacaoProduto['COD_MAPA_SEPARACAO_PRODUTO']);
      $mapaSeparacaoProdutoEn->setDivergencia('S');
      $this->getEntityManager()->persist($mapaSeparacaoProdutoEn);
      }
      $this->getEntityManager()->flush();
      $this->getEntityManager()->commit();
      return 'Existem produtos para serem Conferidos nesta Expedição';
      }
      return true;
      }
     */

    public function verificaMapaSeparacao($expedicaoEn, $idMapa) {

        $result = $this->alteraStatusMapaAndMapaProdutos($expedicaoEn, $idMapa);
        if (is_string($result))
            return $result;

        if ($this->getSystemParameterValue('RESETA_CONFERENCIA_MAPA') == 'S') {
            $this->fechaConferencia($expedicaoEn, $idMapa);
        }

        $mapas = $this->findBy(array('codExpedicao' => $expedicaoEn->getid()));
        foreach ($mapas as $mapaEn) {
            if ($mapaEn->getCodStatus() != Etiqueta::STATUS_CONFERIDO) {
                return 'Existem Mapas para conferir nesta Expedição';
            }
        }

        return true;
    }

    private function alteraStatusMapaAndMapaProdutos($expedicaoEn, $idMapa) {

        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");

        $acertos = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'A');
        $divergencias = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'D');
        foreach ($acertos as $acerto) {
            $idMapaSeparacaoProduto = $acerto['COD_MAPA_SEPARACAO_PRODUTO'];
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('id' => $idMapaSeparacaoProduto));
            $mapaProdutoEn->setIndConferido('S');
            $mapaProdutoEn->setDivergencia('N');
            $this->getEntityManager()->persist($mapaProdutoEn);
        }
        $this->getEntityManager()->flush();

        foreach ($divergencias as $divergenciaProduto) {
            $idMapaProduto = $divergenciaProduto['COD_MAPA_SEPARACAO_PRODUTO'];
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('id' => $idMapaProduto));
            $mapaProdutoEn->setIndConferido('N');
            $mapaProdutoEn->setDivergencia('S');
            $this->getEntityManager()->persist($mapaProdutoEn);
        }
        $this->getEntityManager()->flush();

        if (count($divergencias) > 0) {
            if ($idMapa == null) {
                return 'Existem produtos para serem Conferidos nesta Expedição';
            } else {
                return 'Existem produtos para serem Conferidos no mapa ' . $idMapa;
            }
        }

        if ($idMapa != null) {
            $mapas = $this->findBy(array('id' => $idMapa));
        } else {
            $mapas = $this->findBy(array('codExpedicao' => $expedicaoEn->getId()));
        }

        foreach ($mapas as $mapaEn) {
            $produtosPendentes = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaEn, 'divergencia' => 'S'));
            if (count($produtosPendentes) == 0) {
                $mapaEn->setCodStatus(Etiqueta::STATUS_CONFERIDO);
                $this->getEntityManager()->persist($mapaEn);
            }
        }

        $this->getEntityManager()->flush();
        return 0;
    }

    public function finalizaMapaAjax($codMapa){
        $mapaEn = $this->find($codMapa);
        $mapaEn->setCodStatus(Etiqueta::STATUS_SEPARADO);
        $this->getEntityManager()->persist($mapaEn);
        $this->getEntityManager()->flush();
    }

    private function fechaConferencia($expedicaoEn, $idMapa = null) {
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $mapaConferenciaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoConferencia");

        if ($idMapa != null) {
            $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('id' => $idMapa));
        } else {
            $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('expedicao' => $expedicaoEn));
        }

        foreach ($mapaSeparacaoEn as $mapaSeparacao) {
            $mapaConferenciaEn = $mapaConferenciaRepo->findBy(array('codMapaSeparacao' => $mapaSeparacao->getId(),'indConferenciaFechada' => 'N'));
            foreach ($mapaConferenciaEn as $mapaConferencia) {
                $mapaConferencia->setIndConferenciaFechada('S');
                $this->getEntityManager()->persist($mapaConferencia);
            }
        }
        $this->getEntityManager()->flush();
    }

    public function validaConferencia($expedicao, $setDivergencia = false, $idMapa = null, $tipoRetorno = 'D') {

        if ($tipoRetorno == 'D') {
            // EXIBE SOMENTE AS DIVERGENCIAS
            $sinal = ' < ';
        } else {
            // EXIBE SOMENTE OS ACERTOS
            $sinal = ' = ';
        }

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepository */
        $modeloSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepository->getModeloSeparacao($expedicao);

        $andWhere = ' ';
        if ($setDivergencia == false) {
            $andWhere = " AND MSP.IND_DIVERGENCIA = 'S' ";
        }

        if (isset($modeloSeparacaoEn) && !empty($modeloSeparacaoEn)) {
            $quebra = $modeloSeparacaoEn->getUtilizaQuebraColetor();
            if ($quebra == 'S') {
                if (isset($idMapa) && !empty($idMapa)) {
                    $andWhere .= " AND M.COD_MAPA_SEPARACAO = $idMapa";
                }
            }
        }

        $ncl = Lote::NCL;

        $sql = " SELECT M.COD_MAPA_SEPARACAO,
                          M.COD_PRODUTO,
                          M.DSC_GRADE,
                          P.DSC_PRODUTO,
                          M.QTD_SEPARAR,
                          NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                          M.QTD_SEPARAR - NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIR,
                          NVL(MIN(PE.COD_BARRAS), MIN(PV.COD_BARRAS)) as COD_BARRAS,
                          DE.DSC_DEPOSITO_ENDERECO,
                          MSP.COD_MAPA_SEPARACAO_PRODUTO,
                          MSP.DSC_LOTE
                    FROM (SELECT M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, 
                                 NVL(MP.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MP.QTD_EMBALAGEM * MP.QTD_SEPARAR) - SUM(MP.QTD_CORTADO) as QTD_SEPARAR, NVL(MP.DSC_LOTE, 'NAO_CONTROLA_LOTE') DSC_LOTE
                            FROM MAPA_SEPARACAO_PRODUTO MP
                           INNER JOIN MAPA_SEPARACAO M ON M.COD_MAPA_SEPARACAO = MP.COD_MAPA_SEPARACAO
                           WHERE M.COD_EXPEDICAO = $expedicao AND MP.IND_CONFERIDO = 'N'
                           GROUP BY M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0), NVL(MP.DSC_LOTE, 'NAO_CONTROLA_LOTE')) M
               LEFT JOIN (SELECT MSC.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE, NVL(MSC.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) as QTD_CONFERIDA, NVL(MSC.DSC_LOTE, 'NAO_CONTROLA_LOTE') DSC_LOTE
                            FROM MAPA_SEPARACAO_CONFERENCIA MSC
                           INNER JOIN MAPA_SEPARACAO M ON M.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO
                           WHERE M.COD_EXPEDICAO = $expedicao AND MSC.IND_CONFERENCIA_FECHADA = 'N'  
                           GROUP BY MSC.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE, NVL(MSC.COD_PRODUTO_VOLUME,0), NVL(MSC.DSC_LOTE, 'NAO_CONTROLA_LOTE')) C
                      ON M.COD_MAPA_SEPARACAO = C.COD_MAPA_SEPARACAO
                     AND M.COD_PRODUTO = C.COD_PRODUTO
                     AND M.DSC_GRADE = C.DSC_GRADE
                     AND M.VOLUME = C.VOLUME
                     AND M.DSC_LOTE = C.DSC_LOTE
                LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP
                  ON MSP.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                 AND MSP.COD_PRODUTO = M.COD_PRODUTO
                 AND MSP.DSC_GRADE = M.DSC_GRADE
                 AND CASE WHEN MSP.DSC_LOTE IS NULL THEN '$ncl' ELSE MSP.DSC_LOTE END = M.DSC_LOTE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO P ON P.COD_PRODUTO = M.COD_PRODUTO AND P.DSC_GRADE = M.DSC_GRADE
              WHERE M.COD_EXPEDICAO = $expedicao
                AND NVL(C.QTD_CONFERIDA,0) $sinal M.QTD_SEPARAR
                $andWhere
                GROUP BY M.COD_MAPA_SEPARACAO,
                         M.COD_PRODUTO,
                         M.DSC_GRADE,
                         P.DSC_PRODUTO,
                         M.QTD_SEPARAR,
                         C.QTD_CONFERIDA,
                         DE.DSC_DEPOSITO_ENDERECO,
                         MSP.COD_MAPA_SEPARACAO_PRODUTO,
                         MSP.DSC_LOTE
            ORDER BY COD_MAPA_SEPARACAO, M.COD_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getQtdProdutoMapa($embalagemEn, $volumeEn, $mapaEn, $codPessoa) {
        $sqlVolume = "";
        $sqlPessoa = "";
        $idMapa = $mapaEn->getId();
        $idExpedicao = $mapaEn->getExpedicao()->getId();

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepository */
        $modeloSeparacaoRepository = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");
        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepository->getModeloSeparacao($idExpedicao);

        $quebraColetor = $modeloSeparacaoEn->getUtilizaQuebraColetor();
        if ($quebraColetor == 'S') {
            $whereQuebra = " AND M.COD_MAPA_SEPARACAO = $idMapa";
        } else {
            $whereQuebra = " AND MS.COD_EXPEDICAO = $idExpedicao";
        }
        if ($embalagemEn != null) {
            $grade = $embalagemEn->getProduto()->getGrade();
            $idProduto = $embalagemEn->getProduto()->getId();
        } else {
            $grade = $volumeEn->getProduto()->getGrade();
            $idProduto = $volumeEn->getProduto()->getId();
            $sqlVolume = " AND M.COD_PRODUTO_VOLUME = " . $volumeEn->getId();
        }
        if (isset($codPessoa) && !empty($codPessoa)) {
            $sqlPessoa = " AND M.COD_PEDIDO_PRODUTO IN (
                            SELECT COD_PEDIDO_PRODUTO FROM PEDIDO_PRODUTO WHERE COD_PEDIDO IN (SELECT COD_PEDIDO FROM PEDIDO WHERE COD_PESSOA = $codPessoa)
                         )";
        }

        $SQL = "SELECT SUM(M.QTD_EMBALAGEM * M.QTD_SEPARAR) as QTD, SUM(NVL(M.QTD_CORTADO,0)) QTD_CORTADO
                  FROM MAPA_SEPARACAO_PRODUTO M
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                 WHERE M.COD_PRODUTO = '$idProduto'
                   AND M.DSC_GRADE = '$grade'
                   $sqlVolume
                   $whereQuebra
                   $sqlPessoa ";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    public function validaMapasCortados($pedido) {
        $SQL = "SELECT *
                  FROM PEDIDO_PRODUTO PP
                 INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                 WHERE PP.COD_PEDIDO = '$pedido'
                   AND PP.QUANTIDADE <> NVL(PP.QTD_CORTADA,0)
        ";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getQtdConferenciaAberta($embalagemEn, $volumeEn, $mapaEn, $codPessoa) {
        $sqlVolume = "";
        $idMapa = $mapaEn->getId();
        if ($embalagemEn != null) {
            $grade = $embalagemEn->getProduto()->getGrade();
            $idProduto = $embalagemEn->getProduto()->getId();
        } else {
            $grade = $volumeEn->getProduto()->getGrade();
            $idProduto = $volumeEn->getProduto()->getId();
            $sqlVolume = " AND C.COD_PRODUTO_VOLUME = " . $volumeEn->getId();
        }

        if ($codPessoa == null) {
            $sqlPessoa = " IS NULL";
        } else {
            $sqlPessoa = " = " . $codPessoa;
        }

        $SQL = "SELECT C.NUM_CONFERENCIA, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA
                  FROM MAPA_SEPARACAO_CONFERENCIA C
                 WHERE C.COD_PRODUTO = '$idProduto'
                   AND C.DSC_GRADE = '$grade'
                   AND C.COD_MAPA_SEPARACAO = '$idMapa'
                   $sqlVolume
                   AND C.IND_CONFERENCIA_FECHADA = 'N'
                   AND C.COD_PESSOA " . $sqlPessoa . "
              GROUP BY C.NUM_CONFERENCIA
              ORDER BY C.NUM_CONFERENCIA DESC";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return array('numConferencia' => $result[0]['NUM_CONFERENCIA'],
                'qtd' => $result[0]['QTD_CONFERIDA']);
        } else {
            return null;
        }
    }

    public function getQtdCortadaByMapa($mapaEn, $embalagemEn, $volumeEn) {
        if ($embalagemEn != null) {
            $produtoEn = $embalagemEn->getProduto();
        } else {
            $produtoEn = $volumeEn->getProduto();
        }

        $entidadeMapaProduto = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto')
            ->findBy(array(
                'mapaSeparacao' => $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(),
                'dscGrade' => $produtoEn->getGrade()
            ));
        $qtdCortada = 0;
        foreach ($entidadeMapaProduto as $mapaProduto) {
            $qtdCortada = $qtdCortada + $mapaProduto->getQtdCortado();
        }

        return $qtdCortada;
    }

    public function verificaConferenciaProduto($mapaEn, $idProduto, $grade) {
        /* TESTE DE PERFORMANCE - NÃO VERIFICAR SE TODOS OS PRODUTOS FORAM CONFERIDOS */
        return array(
            'result' => true,
            'msg' => 'Quantidade conferida com sucesso'
        );

        $idMapa = $mapaEn->getId();
        $SQL = "SELECT SEP.COD_PRODUTO, SEP.DSC_GRADE, SEP.QTD_SEP, CONF.QTD_CONF, SEP.QTD_SEP - CONF.QTD_CONF as QTD_PEND
                  FROM (SELECT COD_PRODUTO, DSC_GRADE, SUM((QTD_EMBALAGEM* QTD_SEPARAR)- QTD_CORTADO) as QTD_SEP
                          FROM MAPA_SEPARACAO_PRODUTO
                         WHERE COD_MAPA_SEPARACAO = $idMapa
                         GROUP BY COD_PRODUTO, DSC_GRADE) SEP
                 LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONF
                               FROM MAPA_SEPARACAO_CONFERENCIA
                              WHERE COD_MAPA_SEPARACAO = $idMapa
                              GROUP BY COD_PRODUTO, DSC_GRADE) CONF
                    ON CONF.COD_PRODUTO = SEP.COD_PRODUTO AND CONF.DSC_GRADE = SEP.DSC_GRADE
                 WHERE SEP.QTD_SEP - NVL(CONF.QTD_CONF,0) > 0";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) == 0) {
            $status = $this->getEntityManager()->find('wms:Util\Sigla', Etiqueta::STATUS_CONFERIDO);
            $mapaEn->setStatus($status);
            $this->getEntityManager()->persist($mapaEn);
            $this->getEntityManager()->flush();
            return array('result' => true,
                'msg' => 'Todo o Mapa foi conferido com sucesso!');
        }

        foreach ($result as $produto) {
            if (($produto['COD_PRODUTO'] == $idProduto) && ($produto['DSC_GRADE'] == $grade)) {
                return array('result' => true,
                    'msg' => 'Quantidade conferida com sucesso');
            }
        }

        return array('result' => true,
            'msg' => 'Todos os Produtos ' . $idProduto . ' - ' . $grade . ' foram conferidos com sucesso!');
    }

    /**
     * @param $embalagemEn Embalagem
     * @param $volumeEn Volume
     * @param $mapaEn MapaSeparacao
     * @param $volumePatrimonioEn VolumePatrimonio
     * @param $quantidade
     * @param null $codPessoa
     * @param null $ordemServicoId
     * @param bool $forcaFinalizacao
     * @throws \Exception
     */
    public function adicionaQtdConferidaMapa($embalagemEn, $volumeEn, $mapaEn, $volumePatrimonioEn, $quantidade, $codPessoa = null, $ordemServicoId = null, $forcaFinalizacao = false) {

        $numConferencia = 1;
        $qtdConferida = 0;
        $qtdCortada = 0;
        $qtdMapa = 0;

        $ultConferencia = $this->getQtdConferenciaAberta($embalagemEn, $volumeEn, $mapaEn, $codPessoa);
        $qtdProdutoMapa = $this->getQtdProdutoMapa($embalagemEn, $volumeEn, $mapaEn, $codPessoa);

        if (!empty($qtdProdutoMapa)) {
            $qtdMapa = number_format($qtdProdutoMapa[0]['QTD'], 3, '.', '');
            $qtdCortada = number_format($qtdProdutoMapa[0]['QTD_CORTADO'], 3, '.', '');
        }

        $qtdEmbalagem = 1;
        if ($embalagemEn != null) {
            $produtoEn = $embalagemEn->getProduto();
            $qtdEmbalagem = number_format($embalagemEn->getQuantidade(), 3, '.', '');
        } else {
            $produtoEn = $volumeEn->getProduto();
        }
        if (isset($ordemServicoId) && !empty($ordemServicoId)) {
            $qtdEmbalagem = 1;
        }

        if ($ultConferencia != null) {
            $numConferencia = $ultConferencia['numConferencia'];
            $qtdConferida = number_format($ultConferencia['qtd'], 3, '.', '');
        } else {
            $mapaSeparacaoConferenciaEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia')
                    ->findBy(array('codMapaSeparacao' => $mapaEn->getId(), 'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade(), 'indConferenciaFechada' => 'S'), array('id' => 'DESC'));
            if (isset($mapaSeparacaoConferenciaEn) && !empty($mapaSeparacaoConferenciaEn))
                $numConferencia = $mapaSeparacaoConferenciaEn[0]->getNumConferencia() + 1;
        }

        if ($forcaFinalizacao == false) {
            $qtdDigitada = number_format($qtdEmbalagem, 3, '.', '') * number_format($quantidade, 3, '.', '');
        } else {
            $qtdDigitada = number_format($quantidade, 3, '.', '');
        }
        $qtdBanco = number_format($qtdConferida, 3, '.', '') + number_format($qtdCortada, 3, '.', '');
        $qtdMapa = number_format($qtdMapa, 3, '.', '');

        $quantidadeConferida = Math::adicionar($qtdBanco, $qtdDigitada);
        if ($quantidadeConferida > $qtdMapa) {
            throw new \Exception("Quantidade informada(" . $qtdEmbalagem * $quantidade . ") + $qtdConferida excede a quantidade solicitada no mapa para esse cliente! Produto: " . $produtoEn->getId() . " Mapa:" . $mapaEn->getId());
        }

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $mapaEn->getId(), 'pessoa' => $codPessoa, 'status' => MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));

        if (is_null($ordemServicoId)) {
            $sessao = new \Zend_Session_Namespace('coletor');
            $ordemServicoId = $sessao->osID;
        }

        $novaConferencia = new MapaSeparacaoConferencia();
        $novaConferencia->setCodMapaSeparacao($mapaEn->getId());
        $novaConferencia->setCodOS($ordemServicoId);
        $novaConferencia->setCodProduto($produtoEn->getId());
        $novaConferencia->setDscGrade($produtoEn->getGrade());
        $novaConferencia->setIndConferenciaFechada("N");
        $novaConferencia->setNumConferencia($numConferencia);
        $novaConferencia->setCodProdutoEmbalagem((!empty($embalagemEn))?$embalagemEn->getId(): null);
        $novaConferencia->setCodProdutoVolume((!empty($volumeEn))?$volumeEn->getId(): null);
        $novaConferencia->setQtdEmbalagem($qtdEmbalagem);
        $novaConferencia->setQtdConferida($quantidade);
        $novaConferencia->setVolumePatrimonio($volumePatrimonioEn);
        $novaConferencia->setMapaSeparacaoEmbalado($mapaSeparacaoEmbaladoEn);
        $novaConferencia->setDataConferencia(new \DateTime());
        $novaConferencia->setCodPessoa($codPessoa);
        $this->getEntityManager()->persist($novaConferencia);
        $this->getEntityManager()->flush();
    }

    public function conferenciaMapa($idMapa) {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");
        $listaProdutosNaoConferidosMapa = $mapaSeparacaoRepo->verificaConferenciaMapa($idMapa);
        $todoMapaConferido = true;

        foreach ($listaProdutosNaoConferidosMapa as $produtoNaoConferidoMapa) {
            if ($produtoNaoConferidoMapa['QTD_PRODUTO_CONFERIR'] != 0) {
                $todoMapaConferido = false;
                break;
            }
        }

        if ($todoMapaConferido == true) {
            $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao', $idMapa);
            $mapaSeparacaoEn->setCodStatus(Etiqueta::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapaSeparacaoEn);
            $this->getEntityManager()->flush();
        }
        return $todoMapaConferido;
    }

    public function forcaConferencia($idExpedicao) {
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
        $mapaSeparacaoEmbClienteRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoEmbalado");
        $statusEn = $this->_em->find("wms:Util\Sigla", MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO);

        $mapas = $this->findBy(array('expedicao' => $idExpedicao));
        foreach ($mapas as $mapa) {
            $expedicaoEn = $mapa->getExpedicao();
            $mapaProduto = $mapaSeparacaoProdutoRepo->findBy(array( 'mapaSeparacao' => $mapa->getId(), 'indConferido' => 'N'));
            foreach ($mapaProduto as $produtoEn) {
                $produtoEn->setIndConferido('S');
                $this->getEntityManager()->persist($produtoEn);
            }

            $embalados = $mapaSeparacaoEmbClienteRepo->findBy(['mapaSeparacao' => $mapa]);
            foreach ($embalados as $embalado) {
                $embalado->setStatus($statusEn);
                $this->_em->persist($embalado);
            }

            $mapa->setCodStatus(Etiqueta::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapa);
        }

        if (count($mapas) > 0) {
            $this->fechaConferencia($expedicaoEn);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param $mapaSeparacaoRepo
     * @param $idExpedicao
     * @param $mapaSeparacaoProdutoRepo
     * @param $produtoEn
     * @return mixed
     */
    public function getMapaByProdutoAndExpedicao($idExpedicao, $mapaSeparacaoProdutoRepo, $produtoEn) {
        $mapasEn = $this->findBy(array('expedicao' => $idExpedicao));
        foreach ($mapasEn as $mapaEn) {
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('mapaSeparacao' => $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(),
                'dscGrade' => $produtoEn->getGrade()));
            if ($mapaProdutoEn != null) {
                $mapaEn = $mapaProdutoEn->getMapaSeparacao();
                break;
            }
        }
        return array(
            'mapaEn' => $mapaEn,
            'mapaProdutoEn' => $mapaProdutoEn);
    }

    private function findMapaByProdutoAndExpedicao($produtoEn, $expedicaoEn) {

        $idProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $idExpedicao = $expedicaoEn->getId();

        $SQL = " SELECT MSP.COD_MAPA_SEPARACAO 
                   FROM MAPA_SEPARACAO_PRODUTO MSP
                   LEFT JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MSP.COD_PRODUTO = '$idProduto'
                    AND MSP.DSC_GRADE = '$grade'
                    AND MS.COD_EXPEDICAO = '$idExpedicao'";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result[0]['COD_MAPA_SEPARACAO'];
        }
        return null;
    }

    public function validaProdutoMapa($codBarras, $embalagemEn, $volumeEn, $mapaEn, $modeloSeparacaoEn, $volumePatrimonioEn, $codPessoa = null) {
        /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
        $mensagemColetor = false;
        $produtoEn = null;
        $idMapa = $mapaEn->getId();
        try {
            if (($embalagemEn == null) && ($volumeEn == null)) {
                $mensagemColetor = false;
                throw new \Exception("Nenhum produto encontrado para o código de barras $codBarras");
            }
            if ($embalagemEn != null)
                $produtoEn = $embalagemEn->getProduto();
            else
                $produtoEn = $volumeEn->getProduto();

            $mapaSeparacaoProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade()));
            if ($mapaSeparacaoProduto == null) {
                if ($modeloSeparacaoEn->getUtilizaQuebraColetor() == "S") {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " não se encontra no mapa selecionado");
                } else {
                    $idMapa = $this->findMapaByProdutoAndExpedicao($produtoEn, $mapaEn->getExpedicao());
                    if ($idMapa == null) {
                        $mensagemColetor = true;
                        throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " não se encontra na expedição selecionada");
                    }
                    $mapaSeparacaoProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $idMapa,
                        'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade()));
                }
            }

            $quebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
            $quebraReentrega = $quebraRepo->findOneBy(array('tipoQuebra' => 'RE', 'mapaSeparacao' => $idMapa));

            if ($quebraReentrega == null) {
                $result = $this->getClientesByMapa($idMapa, $codPessoa, $produtoEn->getId(), $produtoEn->getGrade());

                if (count($result) <= 0) {
                    $pessoaEn = $this->getEntityManager()->getRepository('wms:Pessoa')->find($codPessoa);
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " não pertence ao cliente " . $pessoaEn->getNome());
                }
            }
            if ($mapaSeparacaoProduto[0]->getIndConferido() == "S") {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " já está conferido no mapa selecionado");
            }

            $embalado = false;
            if ($embalagemEn != null) {
                if ($modeloSeparacaoEn->getTipoDefaultEmbalado() == "P") {
                    if ($embalagemEn->getEmbalado() == "S") {
                        $embalado = true;
                    }
                } else {
                    $embalagens = $embalagemEn->getProduto()->getEmbalagens();
                    foreach ($embalagens as $emb) {
                        if ($emb->getIsPadrao() == "S") {
                            if ($embalagemEn->getQuantidade() < $emb->getQuantidade()) {
                                $embalado = true;
                            }
                            break;
                        }
                    }
                }
            }

            $dscEmbalagem = "";
            if ($embalagemEn != null) {
                $dscEmbalagem = " - " . $embalagemEn->getDescricao() . " (" . $embalagemEn->getQuantidade() . ") - ";
            }
            if ($modeloSeparacaoEn->getUtilizaVolumePatrimonio() == 'S') {
                if ((isset($volumePatrimonioEn)) && ($volumePatrimonioEn != null) && ($embalado == false)) {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . $dscEmbalagem . " não é embalado");
                }

                if ((!(isset($volumePatrimonioEn)) || ($volumePatrimonioEn == null)) && ($embalado == true)) {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . $dscEmbalagem . " é embalado");
                }
            }
        } catch (\Exception $e) {
            if ($mensagemColetor == true) {
                return array('return' => false, 'message' => $e->getMessage());
            } else {
                throw new \Exception($e->getMessage());
            }
        }
        return array('return' => true, 'idMapa' => $idMapa);
    }

    public function getQtdConferidaByVolumePatrimonio($idExpedicao, $idVolume) {
        $SQL = "SELECT NVL(SUM(MC.QTD_EMBALAGEM * MC.QTD_CONFERIDA),0) as QTD_CONFERIDA
                  FROM MAPA_SEPARACAO_CONFERENCIA MC
                 INNER JOIN (SELECT MAX(NUM_CONFERENCIA) MAX_C, COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0) VOLUME, COD_MAPA_SEPARACAO
                               FROM MAPA_SEPARACAO_CONFERENCIA
                              GROUP BY COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0), COD_MAPA_SEPARACAO) MAX_C
                    ON MAX_C.COD_PRODUTO = MC.COD_PRODUTO
                   AND MAX_C.DSC_GRADE = MC.DSC_GRADE
                   AND MAX_C.COD_MAPA_SEPARACAO = MC.COD_MAPA_SEPARACAO
                   AND MAX_C.VOLUME = NVL(MC.COD_PRODUTO_VOLUME,0)
                  LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MC.COD_MAPA_SEPARACAO
                 WHERE MC.COD_VOLUME_PATRIMONIO = $idVolume
                   AND MS.COD_EXPEDICAO = $idExpedicao";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        return $result[0]['QTD_CONFERIDA'];
    }

    public function getMapaSeparacaoByExpedicao($idExpedicao) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('ms.id codBarras, ms.dscQuebra descricao')
                ->from('wms:Expedicao\MapaSeparacao', 'ms')
                ->where("ms.expedicao = $idExpedicao");

        return $dql->getQuery()->getResult();
    }

    public function verificaConferenciaMapa($idMapaSeparacao) {
        $sql = "SELECT SUM(NVL(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM, 0)) - (NVL(MSC.QTD_CONFERIDA, 0) + SUM(MSP.QTD_CORTADO)) AS QTD_PRODUTO_CONFERIR, SUM(NVL(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM, 0)), NVL(MSC.QTD_CONFERIDA, 0), MSP.COD_PRODUTO
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_CONFERIDA * MSC.QTD_EMBALAGEM) QTD_CONFERIDA, MS1.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE
                  FROM MAPA_SEPARACAO MS1
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS1.COD_MAPA_SEPARACAO
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS1.COD_MAPA_SEPARACAO ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao
                GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MSC.QTD_CONFERIDA ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getClientesByMapa($idMapaSeparacao, $codPessoa = null, $idProduto = null, $grade = null) {
        $andWhere = '';
        if (isset($codPessoa) && !empty($codPessoa)) {
            $andWhere = ' AND P.COD_PESSOA = ' . $codPessoa;
        }
        if (isset($idProduto) && !empty($idProduto) && isset($grade) && !empty($grade)) {
            $andWhere .= " AND PROD.COD_PRODUTO = '$idProduto' AND PROD.DSC_GRADE = '$grade' ";
        }
        $sql = "SELECT P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                    INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA
                    LEFT JOIN (
                      SELECT MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO
                      FROM MAPA_SEPARACAO_PRODUTO MSP
                      WHERE MSP.COD_MAPA_SEPARACAO = $idMapaSeparacao
                    ) MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                    INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE

                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao
                $andWhere
                GROUP BY P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
                ORDER BY MSPROD.NUM_CAIXA_PC_INI";

        $sql = "
                SELECT P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
                FROM (
                      SELECT MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO
                      FROM MAPA_SEPARACAO_PRODUTO MSP
                      WHERE MSP.COD_MAPA_SEPARACAO = $idMapaSeparacao
                    ) MSPROD
              INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSPROD.COD_PEDIDO_PRODUTO
              INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
              INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
              INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA
              WHERE MSPROD.COD_MAPA_SEPARACAO = $idMapaSeparacao
              $andWhere
              GROUP BY P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
              ORDER BY MSPROD.NUM_CAIXA_PC_INI";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getClientesByConferencia($idMapaSeparacao, $agrupaEmbalado = false, $usaCaixaPadrao = false) {
        $statusEmbalado = MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO;
        $where = (!($agrupaEmbalado && $usaCaixaPadrao)) ? "MSP.QTD_SEPARAR > NVL(MSC.QTD_CONFERIDA,0)" : "1 = 1";

        $sql = "SELECT P.NOM_PESSOA,
                       P.COD_PESSOA,
                       MIN(MSP.NUM_CAIXA_PC_INI) NUM_CAIXA_PC_INI,
                       MAX(MSP.NUM_CAIXA_PC_FIM) NUM_CAIXA_PC_FIM
                 FROM (SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR,
                              MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM,
                              MSP.COD_MAPA_SEPARACAO,
                              MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE
                         FROM MAPA_SEPARACAO_PRODUTO MSP
                        WHERE MSP.COD_MAPA_SEPARACAO = $idMapaSeparacao
                        GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO,
                                 MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE, MSP.COD_MAPA_SEPARACAO) MSP
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = PP.COD_PEDIDO
                INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA
                 LEFT JOIN (SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA
                              FROM MAPA_SEPARACAO_CONFERENCIA MSC
                             WHERE MSC.COD_MAPA_SEPARACAO = $idMapaSeparacao
                             GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA) MSC
                        ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                       AND MSC.COD_PRODUTO = MSP.COD_PRODUTO
                       AND MSC.DSC_GRADE = MSP.DSC_GRADE
                       AND MSC.COD_PESSOA = P.COD_PESSOA
                 LEFT JOIN MAPA_SEPARACAO_EMB_CLIENTE MSEC ON MSEC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO AND MSEC.COD_PESSOA = PED.COD_PESSOA
                 WHERE $where OR MSEC.COD_STATUS = $statusEmbalado
                 GROUP BY P.NOM_PESSOA, P.COD_PESSOA
                 ORDER BY MIN(MSP.NUM_CAIXA_PC_INI)";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getMapaSeparacaoById($codMapas) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('ms')
                ->from('wms:Expedicao\MapaSeparacao', 'ms')
                ->where("ms.id IN ($codMapas)");

        return $dql->getQuery()->getResult();
    }

    public function getResumoConferenciaEmbalados($idExpedicao) {
        $sql = "SELECT MS.COD_MAPA_SEPARACAO, MSC.COD_MAPA_SEPARACAO_EMB_CLIENTE, P.NOM_PESSOA, S.DSC_SIGLA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSC.COD_PESSOA
                    INNER JOIN SIGLA S ON S.COD_SIGLA = MSC.COD_STATUS
                    WHERE MS.COD_EXPEDICAO = $idExpedicao
                    ORDER BY MS.COD_MAPA_SEPARACAO ASC, MSC.COD_MAPA_SEPARACAO_EMB_CLIENTE ASC";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferidosByClientes($idMapa, $codPessoa) {

        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA, LISTAGG(MSPROD.NUM_CAIXA_PC_INI, ',') WITHIN GROUP (ORDER BY MSPROD.NUM_CAIXA_PC_INI) AS NUM_CAIXA_PC_INI, MSPROD.COD_PRODUTO, MSPROD.DSC_GRADE, PROD.DSC_PRODUTO
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
                INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA AND P.COD_PESSOA = $codPessoa
                INNER JOIN (
                  SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR, MSP.NUM_CAIXA_PC_INI,
                  MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE, MSP.COD_MAPA_SEPARACAO ) MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PROD.COD_PRODUTO AND MSC.DSC_GRADE = PROD.DSC_GRADE AND MSC.COD_PESSOA = P.COD_PESSOA
                WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND MSPROD.QTD_SEPARAR > NVL(MSC.QTD_CONFERIDA,0)
                GROUP BY P.NOM_PESSOA, P.COD_PESSOA, MSPROD.COD_PRODUTO, MSPROD.DSC_GRADE, PROD.DSC_PRODUTO
                ORDER BY NUM_CAIXA_PC_INI";

        $naoControlaLote = Lote::NCL;

        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA, LISTAGG(MSP.NUM_CAIXA_PC_INI, ',') WITHIN GROUP (ORDER BY MSP.NUM_CAIXA_PC_INI) AS NUM_CAIXA_PC_INI, MSP.COD_PRODUTO, MSP.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE, MSP.DSC_LOTE
                  FROM (SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR,
                               MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM,
                               MSP.COD_MAPA_SEPARACAO,
                               MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE,
                               NVL(MSP.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                          FROM MAPA_SEPARACAO_PRODUTO MSP
                         WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                         GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO,
                                  MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE, MSP.COD_MAPA_SEPARACAO, MSP.DSC_LOTE) MSP
                 INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                 INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = PP.COD_PEDIDO
                 INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA AND P.COD_PESSOA = $codPessoa
                 INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN (SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA, NVL(MSC.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                               FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                              GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA, MSC.DSC_LOTE) MSC
                         ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                        AND MSC.COD_PRODUTO = MSP.COD_PRODUTO
                        AND MSC.DSC_GRADE = MSP.DSC_GRADE
                        AND MSC.COD_PESSOA = P.COD_PESSOA
                        AND MSC.DSC_LOTE = MSP.DSC_LOTE
                WHERE MSP.QTD_SEPARAR > NVL(MSC.QTD_CONFERIDA,0)
                 GROUP BY P.NOM_PESSOA, P.COD_PESSOA, MSP.COD_PRODUTO, MSP.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE, MSP.DSC_LOTE
                  ORDER BY PROD.DSC_PRODUTO, NUM_CAIXA_PC_INI";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getProdutosConferidosTotalByClientes($idMapa, $codPessoa)
    {
        $naoControlaLote = Lote::NCL;

        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA, LISTAGG(MSP.NUM_CAIXA_PC_INI, ',') WITHIN GROUP (ORDER BY MSP.NUM_CAIXA_PC_INI) AS NUM_CAIXA_PC_INI, MSP.COD_PRODUTO, MSP.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE, NVL(MSC.QTD_CONFERIDA,0) as QTD_CONFERIDA, MSP.QTD_SEPARAR, MSP.DSC_LOTE
                  FROM (SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR,
                               MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM,
                               MSP.COD_MAPA_SEPARACAO,
                               MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE,
                               NVL(MSP.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                          FROM MAPA_SEPARACAO_PRODUTO MSP
                         WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                         GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO,
                                  MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE, MSP.COD_MAPA_SEPARACAO, MSP.DSC_LOTE) MSP
                 INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                 INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = PP.COD_PEDIDO
                 INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA AND P.COD_PESSOA = $codPessoa
                 INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN (SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA, NVL(MSC.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                               FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                              GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, MSC.COD_PESSOA, MSC.DSC_LOTE) MSC
                         ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                        AND MSC.COD_PRODUTO = MSP.COD_PRODUTO
                        AND MSC.DSC_GRADE = MSP.DSC_GRADE
                        AND MSC.COD_PESSOA = P.COD_PESSOA
                        AND MSC.DSC_LOTE = MSP.DSC_LOTE
                WHERE NVL(MSC.QTD_CONFERIDA,0) > 0
                 GROUP BY P.NOM_PESSOA, P.COD_PESSOA, MSP.COD_PRODUTO, MSP.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE, MSC.QTD_CONFERIDA, MSP.QTD_SEPARAR, MSP.DSC_LOTE
                  ORDER BY PROD.DSC_PRODUTO, NUM_CAIXA_PC_INI";
//        WHERE MSP.QTD_SEPARAR = NVL(MSC.QTD_CONFERIDA,0)
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function confereMapaProduto($paramsModeloSeparaco, $idExpedicao, $idMapa, $codBarras, $qtd, $volumePatrimonioEn, $cpfEmbalador, $codPessoa = null, $ordemServicoId = null, $checkout = false, $lote = Lote::NCL) {

        try {

            if (!empty($volumePatrimonioEn)) {
                $idVolumePatrimonio = $volumePatrimonioEn->getId();
            } else {
                $idVolumePatrimonio = "NULL";
            }

            $parametrosConferencia = array(
                'idVolumePatrimonio' => $idVolumePatrimonio,
                'codPessoa' => $codPessoa,
                'qtd' => $qtd,
                'codBarras' => $codBarras,
                'idMapa' => $idMapa,
                'idExpedicao' => $idExpedicao,
                'lote' => $lote
            );

            $conferencia = $this->validaConferenciaMapaProduto($parametrosConferencia,$paramsModeloSeparaco);

            if (is_null($ordemServicoId)) {
                $sessao = new \Zend_Session_Namespace('coletor');
                $ordemServicoId = $sessao->osID;
            }

            $idMapaSepEmb = "NULL";
            if (!empty($codPessoa)) {
                /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
                $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
                $sql = "SELECT * FROM MAPA_SEPARACAO_EMB_CLIENTE WHERE COD_MAPA_SEPARACAO = $idMapa AND COD_PESSOA = $codPessoa ORDER BY COD_MAPA_SEPARACAO_EMB_CLIENTE DESC";
                $mapaSeparacaoEmbalado = $this->_em->getConnection()->query($sql)->fetch();
                if (empty($mapaSeparacaoEmbalado)) {
                    $osEmbalamento = $mapaSeparacaoEmbaladoRepo->getOsEmbalagem($cpfEmbalador, $idExpedicao, true);
                    $idMapaSepEmb = $mapaSeparacaoEmbaladoRepo->save($idMapa, $codPessoa,  $osEmbalamento, false);
                } else {
                    if (in_array($mapaSeparacaoEmbalado['COD_STATUS'], [Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO, Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO])) {
                        $osEmbalamento = $mapaSeparacaoEmbaladoRepo->getOsEmbalagem($cpfEmbalador, $idExpedicao, true);
                        $idMapaSepEmb = $mapaSeparacaoEmbaladoRepo->save($idMapa, $codPessoa, $osEmbalamento, false);
                    } else {
                        $idMapaSepEmb = $mapaSeparacaoEmbalado['COD_MAPA_SEPARACAO_EMB_CLIENTE'];
                    }
                }
            } else {
                $codPessoa = "NULL";
            }

            $dataConferencia = (new \DateTime())->format("d/m/Y H:i:s");

            $conn = $this->_em->getConnection();
            foreach ($conferencia as $conf) {

                $idVolume = (!empty($conf['codPrdutoVolume']))? $conf['codPrdutoVolume'] : 'NULL';
                $idEmbalagem = (!empty($conf['codProdutoEmbalagem']))? $conf['codProdutoEmbalagem'] : 'NULL';

                $sql = "
                    INSERT INTO MAPA_SEPARACAO_CONFERENCIA 
                        (
                         COD_MAPA_SEPARACAO_CONFERENCIA, 
                         COD_MAPA_SEPARACAO, 
                         COD_PRODUTO, 
                         DSC_GRADE, 
                         COD_PRODUTO_VOLUME, 
                         COD_PRODUTO_EMBALAGEM, 
                         QTD_EMBALAGEM, 
                         QTD_CONFERIDA, 
                         COD_OS, 
                         NUM_CONFERENCIA, 
                         DTH_CONFERENCIA, 
                         COD_VOLUME_PATRIMONIO, 
                         COD_MAPA_SEPARACAO_EMBALADO, 
                         COD_PESSOA, 
                         DSC_LOTE
                         ) 
                     VALUES (
                             SQ_MAPA_SEPARACAO_CONF_01.nextval,
                             $conf[codMapaSeparacao],
                             '$conf[codProduto]',
                             '$conf[dscGrade]',
                             $idVolume,
                             $idEmbalagem,
                             $conf[qtdEmbalagem],
                             $conf[quantidade],
                             $ordemServicoId,
                             $conf[numConferencia],
                             TO_DATE('$dataConferencia', 'DD/MM/YYYY HH24:MI:SS'),
                             $idVolumePatrimonio,
                             $idMapaSepEmb,
                             $codPessoa,
                             '$conf[lote]'
                             ) 
                ";
                $conn->executeQuery($sql);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $this->getEntityManager()->flush();

        if($checkout == true){
            return  $this->validaConferenciaMapaProduto($parametrosConferencia,$paramsModeloSeparaco, $checkout);
        }

        return true;

    }

    /**
     * @param $dadosConferencia
     * @param $paramsModeloSeparacao
     * @param bool $checkout
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function validaConferenciaMapaProduto($dadosConferencia, $paramsModeloSeparacao, $checkout = false) {

        $idExpedicao = $dadosConferencia['idExpedicao'];
        $idMapa = $dadosConferencia['idMapa'];
        $codBarras = $dadosConferencia['codBarras'];
        $qtd = $dadosConferencia['qtd'];
        $codPessoa = $dadosConferencia['codPessoa'];
        $lote = (!empty($dadosConferencia['lote']) && $dadosConferencia['lote'] != Lote::NCL) ? $dadosConferencia['lote'] : null ;

        $ncl = Lote::NCL;

        $utilizaQuebra = $paramsModeloSeparacao['utilizaQuebra'];
        $tipoDefaultEmbalado = $paramsModeloSeparacao['tipoDefaultEmbalado'];
        $utilizaVolumePatrimonio = $paramsModeloSeparacao['utilizaVolumePatrimonio'];

        $whereMSPEmbalado = "";
        $whereMSCEmbalado = "";
        $whereOnNaoConsolidado = "";
        if ($codPessoa != null) {
            $whereMSPEmbalado = "
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                WHERE P.COD_PESSOA = " . $codPessoa;
            $whereMSCEmbalado = "
                WHERE COD_PESSOA = " . $codPessoa;
        } else {
            $whereOnNaoConsolidado = "AND (MSQ.IND_TIPO_QUEBRA <> 'T' OR MSQ.IND_TIPO_QUEBRA IS NULL)";
        }

        //SE O INDICADOR DE EMBALADO NAO FOR O PRODUTO E SIM A EMBALAGEM FRACIONADA, ENTÂO JA RETORNA ISSO NA QUERY
        $SQLFields = "";
        $SQLJoin = "";
        if ($tipoDefaultEmbalado != "P") {
            $SQLFields = " PEP.QTD_EMBALAGEM as QTD_EMBALAGEM_PADRAO, ";
            $SQLJoin   = " LEFT JOIN PRODUTO_EMBALAGEM PEP ON PEP.COD_PRODUTO = MSP.COD_PRODUTO AND PEP.DSC_GRADE = MSP.DSC_GRADE AND PEP.IND_PADRAO = 'S'";
        }

        //QUERY PRINCIPAL PARA VALIDAÇÃO DE CONFERENCIA
        $SQL = "SELECT DISTINCT $SQLFields
                       MS.COD_MAPA_SEPARACAO,
                       CASE WHEN MS.COD_MAPA_SEPARACAO = $idMapa THEN 0 ELSE 1 END as ORDENADOR,
                       MSP.QTD_SEPARAR,
                       P.DSC_PRODUTO,
                       P.COD_PRODUTO,
                       P.DSC_GRADE,
                       NVL(P.IND_CONTROLA_LOTE, 'N') as IND_CONTROLA_LOTE,
                       MSP.DSC_LOTE,
                       P.IND_FRACIONAVEL,
                       PE.COD_PRODUTO_EMBALAGEM,
                       PV.COD_PRODUTO_VOLUME,
                       NVL(PE.DSC_EMBALAGEM,PV.DSC_VOLUME) as DSC_EMBALAGEM,
                       NVL(PE.QTD_EMBALAGEM,1) as QTD_EMBAlAGEM,
                       NVL(CONF.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                       NVL(PE.IND_EMBALADO,'N') as IND_EMBALADO,
                       NVL(PE.IS_EMB_FRACIONAVEL_DEFAULT, 'N') as IS_EMB_FRACIONAVEL_DEFAULT,
                       NVL(PE.IS_EMB_EXPEDICAO_DEFAULT, 'N') as IS_EMB_EXP_DEFAULT
                  FROM MAPA_SEPARACAO MS
                  LEFT JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MS.COD_MAPA_SEPARACAO = MSQ.COD_MAPA_SEPARACAO
                  INNER JOIN (SELECT COD_MAPA_SEPARACAO, MSP.COD_PRODUTO, MSP.DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) COD_PRODUTO_VOLUME,
                                    SUM((QTD_EMBALAGEM * QTD_SEPARAR) - NVL(QTD_CORTADO,0)) as QTD_SEPARAR, NVL(MSP.DSC_LOTE, '$ncl') DSC_LOTE
                               FROM MAPA_SEPARACAO_PRODUTO MSP
                               $whereMSPEmbalado
                              GROUP BY COD_MAPA_SEPARACAO, MSP.COD_PRODUTO, MSP.DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0), NVL(MSP.DSC_LOTE, '$ncl')) MSP ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                  LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) COD_PRODUTO_VOLUME, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA, 
                              NVL(DSC_LOTE, '$ncl') DSC_LOTE
                               FROM MAPA_SEPARACAO_CONFERENCIA
                               $whereMSCEmbalado
                             GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0), NVL(DSC_LOTE, '$ncl')) CONF
                         ON CONF.COD_PRODUTO = MSP.COD_PRODUTO
                        AND CONF.DSC_GRADE = MSP.DSC_GRADE
                        AND CONF.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                        AND CONF.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                        AND CONF.DSC_LOTE = MSP.DSC_LOTE
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = MSP.COD_PRODUTO AND PE.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                  $SQLJoin
                 WHERE 1 = 1
                    $whereOnNaoConsolidado
                    AND ((PE.COD_BARRAS = '$codBarras' AND PE.DTH_INATIVACAO IS NULL) OR (PV.COD_BARRAS = '$codBarras' AND PV.DTH_INATIVACAO IS NULL))";

        //SE UTIILIZAR QUEBRA NA CONFERENCIA ENTÃO COMPARO APENAS COM O MAPA INFORMADO, CASO CONTRARIO COMPARO COM TODOS OS MAPAS DA EXPEDIÇÃO
        if ($utilizaQuebra == "S") {
            $SQL = $SQL . " AND MSP.COD_MAPA_SEPARACAO = $idMapa";
        } else {
            $SQL = $SQL . " AND MS.COD_EXPEDICAO = $idExpedicao";
        }

        $SQL .= " ORDER BY ORDENADOR";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        //VERIFICO SE O CÓDIGO DE BARRAS PERTENCE A ALGUM PRODUTO DO MAPA
        if (count($result) == 0) {
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $produtoEn = $produtoRepo->getProdutoByCodBarrasOrCodProduto($codBarras);
            $msgErro = "O Produto " . $produtoEn->getDescricao() . " não pertence ";
            if ($codPessoa != null) {
                $msgErro .= " ao cliente selecionado";
            } else {
                if ($utilizaQuebra == "S") {
                    $msgErro .= " ao mapa " . $idMapa;
                } else {
                    $msgErro .= " a expedicao " . $idExpedicao;
                }
            }
            throw new \Exception($msgErro);
        }

        $fatorCodBarrasBipado = $result[0]['QTD_EMBALAGEM'];
        $codBarrasEmbalado = $result[0]['IND_EMBALADO'];
        $codProdutoEmbalagem = $result[0]['COD_PRODUTO_EMBALAGEM'];
        $codProdutoVolume = $result[0]['COD_PRODUTO_VOLUME'];
        $dscProduto = $result[0]['DSC_PRODUTO'];
        $codProduto = $result[0]['COD_PRODUTO'];
        $dscGrade = $result[0]['DSC_GRADE'];
        $dscEmbalagem = $result[0]['DSC_EMBALAGEM'] . "($fatorCodBarrasBipado)";
        $prodFracionavel = $result[0]['IND_FRACIONAVEL'];
        $isEmbExpDefault = $result[0]['IS_EMB_EXP_DEFAULT'];

        if ($prodFracionavel == 'S') {
            /** @var EmbalagemRepository $embalagemRepo */
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            /** @var Embalagem $embExpDefault */
            $embExpDefault = $embalagemRepo->findOneBy(['codProduto' => $codProduto, 'grade' => $dscGrade, 'isEmbExpDefault' => 'S']);
            if (!empty($embFracDefault) && $isEmbExpDefault != 'S') {
                throw new \Exception("Este produto $codProduto - $dscGrade só pode ser expedido na embalagem " . $embExpDefault->getDescricao());
            }
        } else {
            if (Math::resto($qtd, 1) > 0) {
                throw new \Exception("O produto $codProduto - $dscGrade não pode ser expedido em uma fração da menor embalagem!");
            }
        }

        //CALCULO A QUANTIDADE PENDENTE DE CONFERENCIA PARA CADA MAPA, SE UTILIZAR QUEBRA O FILTRO VAI TRAZER APENAS UM MAPA
        $qtdConferidoTotal = 0;
        $qtdMapaTotal = 0;
        $qtdInformada = Math::multiplicar($qtd, $fatorCodBarrasBipado);

        $qtdConferenciaGravar = array();
        $qtdRestante = $qtdInformada;
        foreach ($result as $mapa) {
            //CASO SEJA CONFERÊNCIA DE EMBALADO NÃO SOMA AS QTDS DO MESMO ITEM DE TODOS OS MAPAS
            if (!empty($codPessoa) && $mapa['COD_MAPA_SEPARACAO'] != $idMapa) continue;

            //CASO O PRODUTO CONTROLE LOTE, SÓ CALCULA O LOTE ESPECÍFICO
            if ($mapa['IND_CONTROLA_LOTE'] == 'S' && !empty($mapa["DSC_LOTE"]) && $mapa["DSC_LOTE"] != $lote) continue;

            $qtdMapaTotal = Math::adicionar($qtdMapaTotal, $mapa['QTD_SEPARAR']);
            $qtdConferidoTotal = Math::adicionar($qtdConferidoTotal, $mapa['QTD_CONFERIDA']);
            $qtdPendenteConferenciaMapa = Math::subtrair($mapa['QTD_SEPARAR'], $mapa['QTD_CONFERIDA']);

            $codMapa = $mapa['COD_MAPA_SEPARACAO'];

            if (Math::compare($qtdRestante, $qtdPendenteConferenciaMapa, "<=")) {
                $qtdConferir = $qtdRestante;
            } else {
                $qtdConferir = (!$checkout) ? $qtdPendenteConferenciaMapa: $qtdRestante ;
            }

            $qtdConferidoTotalEmb = $qtdConferidoTotal;
            if ($checkout && $qtdConferidoTotal > 0) {
                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
                $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($codProduto, $dscGrade, $qtdConferidoTotal);
                $qtdConferidoTotalEmb = implode(' + ', $vetSeparar);
            }
            if ($qtdConferir > 0) {
                $qtdConferenciaGravar[] = array(
                    'codMapaSeparacao' => $codMapa,
                    'codProduto' => $codProduto,
                    'dscGrade' => $dscGrade,
                    'numConferencia' => 1,
                    'codProdutoEmbalagem' => $codProdutoEmbalagem,
                    'codPrdutoVolume' => $codProdutoVolume,
                    'qtdEmbalagem' => $fatorCodBarrasBipado,
                    'qtdConferidaTotalEmb' => $qtdConferidoTotalEmb,
                    'quantidade' => Math::dividir($qtdConferir, $fatorCodBarrasBipado),
                    'lote' => (!empty($lote))? $lote : Lote::NCL
                );

                $qtdRestante = Math::subtrair($qtdRestante, $qtdConferir);
            }
        }

        if (Math::compare($qtdRestante, 0, ">") && !$checkout) {
            $strLote = (!empty($lote)) ? " lote: '$lote'" : "";
            throw new \Exception("A quantidade de $qtdInformada para o produto $codProduto ($dscProduto) - $dscGrade$strLote excede o solicitado!");
        }

        //VERIFICO SE O PRODUTO JA FOI COMPELTAMENTE CONFERIDO NO MAPA OU NA EXPEDIÇÃO DE ACORDO COM O PARAMETRO DE UTILIZAR QUEBRA NA CONFERENCIA
        if($checkout == true) {
            if ($qtdMapaTotal == $qtdConferidoTotal) {
                return array('produto' => $qtdConferenciaGravar, 'checkout' => 'checkout');
            }else{
                return array('produto' => $qtdConferenciaGravar);
            }
        }
        if ($qtdMapaTotal == $qtdConferidoTotal) {
            $msgErro = "O produto $dscProduto já se encontra totalmente conferido ";
            if ($codPessoa != null) {
                $msgErro .= "para o cliente selecionado";
            } else {
                if ($utilizaQuebra == "S") {
                    $msgErro .= "no mapa " . $idMapa;
                } else {
                    $msgErro .= "na expedicao " . $idExpedicao;
                }
            }
            throw new \Exception($msgErro);
        } elseif (Math::compare($qtdInformada, Math::subtrair($qtdMapaTotal,$qtdConferidoTotal), '>')) {
            throw new \Exception("A quantidade de $qtdInformada excede o solicitado!");
        }

        //VERIFCO SE O PRODUTO É EMBALADO E ESTA UTILIZANDO VOLUME PATRIMONIO
        $embalado = false;
        if ($tipoDefaultEmbalado == "P") {
            if ($codBarrasEmbalado == "S") {
                $embalado = true;
            }
        } else {
            $QtdPadraoRecebimento = $result[0]['QTD_EMBALAGEM_PADRAO'];
            if ($fatorCodBarrasBipado < $QtdPadraoRecebimento) {
                $embalado = true;
            }
        }


        if ($utilizaVolumePatrimonio == 'S') {
            if ((!(isset($dadosConferencia['idVolumePatrimonio'])) || ($dadosConferencia['idVolumePatrimonio'] == null)) && ($embalado == true)) {
                throw new \Exception("O produto $codProduto / $dscGrade - $dscProduto - $dscEmbalagem é embalado");
            }
        }

        return $qtdConferenciaGravar;
    }

    public function findMapasSeparar($pedido = null){

        $sqlWhere = " ";
        if ($pedido != null) {
            $sqlWhere = " AND MS.COD_MAPA_SEPARACAO IN (
            SELECT COD_MAPA_SEPARACAO
              FROM MAPA_SEPARACAO_PEDIDO MSP
              LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
              LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
             WHERE P.COD_EXTERNO = '$pedido')";
        }

        $sql = "SELECT MS.COD_MAPA_SEPARACAO, MS.COD_EXPEDICAO 
                  FROM MAPA_SEPARACAO MS
                  LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = MS.COD_EXPEDICAO 
                 WHERE MS.COD_STATUS = 523 
                   AND E.COD_STATUS IN (463,464,551)
                   $sqlWhere
                 ORDER BY MS.COD_MAPA_SEPARACAO";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findEnderecosMapa($codMapaSeparacao){
        $sql = "SELECT 
                  DISTINCT(MPS.COD_DEPOSITO_ENDERECO), DE.DSC_DEPOSITO_ENDERECO, MPS.COD_MAPA_SEPARACAO_PRODUTO
                FROM 
                  MAPA_SEPARACAO_PRODUTO MPS
                  INNER JOIN DEPOSITO_ENDERECO DE ON MPS.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                WHERE COD_MAPA_SEPARACAO = $codMapaSeparacao  AND
                  (MPS.IND_SEPARADO = 'N' OR MPS.IND_SEPARADO IS NULL)
                ORDER BY DE.DSC_DEPOSITO_ENDERECO";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $return = array();
        foreach ($result as $value){
            $return[$value['DSC_DEPOSITO_ENDERECO']]['DSC_DEPOSITO_ENDERECO'] = $value['DSC_DEPOSITO_ENDERECO'];
            $return[$value['DSC_DEPOSITO_ENDERECO']]['COD_DEPOSITO_ENDERECO'] = $value['COD_DEPOSITO_ENDERECO'];
        }
        return $return;
    }

    public function getProdutosMapaEndereco($endereco, $codMapa){
        $sql = "SELECT SUM(NVL((((MPS.QTD_SEPARAR - MPS.QTD_CORTADO) * MPS.QTD_EMBALAGEM) - SMS.TOTAL),(MPS.QTD_SEPARAR - MPS.QTD_CORTADO)  * MPS.QTD_EMBALAGEM))  AS SEPARAR,
                       P.DSC_GRADE,
                       P.COD_PRODUTO,
                       P.DSC_PRODUTO,
                       PV.DSC_VOLUME,
                       DE.DSC_DEPOSITO_ENDERECO,
                       MPS.DSC_LOTE,
                       MPS.COD_DEPOSITO_ENDERECO
                  FROM MAPA_SEPARACAO_PRODUTO MPS
                 INNER JOIN DEPOSITO_ENDERECO DE ON MPS.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                 INNER JOIN PRODUTO P ON (P.COD_PRODUTO = MPS.COD_PRODUTO AND P.DSC_GRADE = MPS.DSC_GRADE)
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON (PE.COD_PRODUTO_EMBALAGEM = MPS.COD_PRODUTO_EMBALAGEM)
                  LEFT JOIN PRODUTO_VOLUME PV ON (PV.COD_PRODUTO_VOLUME = MPS.COD_PRODUTO_VOLUME)
                  LEFT JOIN (SELECT SUM(QTD_SEPARADA * QTD_EMBALAGEM) AS TOTAL, 
                                    COD_PRODUTO, DSC_GRADE, 
                                    COD_MAPA_SEPARACAO, COD_PRODUTO_EMBALAGEM, DSC_LOTE
                               FROM SEPARACAO_MAPA_SEPARACAO  
                              GROUP BY COD_PRODUTO, DSC_GRADE, COD_MAPA_SEPARACAO, COD_PRODUTO_EMBALAGEM, DSC_LOTE) SMS 
                    ON (SMS.COD_PRODUTO = MPS.COD_PRODUTO) 
                   AND (SMS.DSC_GRADE = MPS.DSC_GRADE) 
                   AND (MPS.COD_MAPA_SEPARACAO = SMS.COD_MAPA_SEPARACAO)
                   AND (NVL(MPS.DSC_LOTE,0) = NVL(SMS.DSC_LOTE,0))  
                   AND (MPS.COD_PRODUTO_EMBALAGEM = SMS.COD_PRODUTO_EMBALAGEM)
                WHERE 
                  DE.DSC_DEPOSITO_ENDERECO = '$endereco' AND 
                  MPS.COD_MAPA_SEPARACAO = $codMapa AND
                  (MPS.IND_SEPARADO = 'N' OR MPS.IND_SEPARADO IS NULL)
                GROUP BY 
                    P.DSC_GRADE,
                    P.COD_PRODUTO,
                    P.DSC_PRODUTO,
                    PV.DSC_VOLUME,
                    DE.DSC_DEPOSITO_ENDERECO,
                    MPS.DSC_LOTE,
                    MPS.COD_DEPOSITO_ENDERECO";
        $result =  $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $return = array();
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $keyEmb = 0;
        foreach ($result as $key => $value){
            if ($value['SEPARAR'] > 0) {
                $embalagens = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['SEPARAR'], 1);
                foreach ($embalagens as $emb){
                    $return[$keyEmb]['DSC_GRADE'] = $value['DSC_GRADE'];
                    $return[$keyEmb]['COD_PRODUTO'] = $value['COD_PRODUTO'];
                    $return[$keyEmb]['DSC_PRODUTO'] = $value['DSC_PRODUTO'];
                    $return[$keyEmb]['DSC_VOLUME'] = $value['DSC_VOLUME'];
                    $return[$keyEmb]['DSC_LOTE'] = $value['DSC_LOTE'];
                    $return[$keyEmb]['DSC_DEPOSITO_ENDERECO'] = $value['DSC_DEPOSITO_ENDERECO'];
                    $return[$keyEmb]['COD_DEPOSITO_ENDERECO'] = $value['COD_DEPOSITO_ENDERECO'];
                    $return[$keyEmb]['SEPARAR'] = $emb['qtd'];
                    $return[$keyEmb]['QTD_EMBALAGEM'] = $emb['qtdEmbalagem'];
                    $return[$keyEmb]['DSC_EMBALAGEM'] = $emb['dsc'].' ( '.$emb['qtdEmbalagem'].' )';
                    $keyEmb++;
                }
            }
        }
        return $return;
    }

    public function getMapaPendenteSeparacao($codExpedicao = null, $codMapaSeparacao = null)
    {
        $where = '';
        if (!is_null($codExpedicao)) {
            $where .= " AND MS.COD_EXPEDICAO = $codExpedicao ";
        }
        if (!is_null($codMapaSeparacao)) {
            $where .= " AND MS.COD_MAPA_SEPARACAO = $codMapaSeparacao ";
        }

        $sql = "SELECT MS.COD_MAPA_SEPARACAO,
                    MSP.COD_PRODUTO,
                    MSP.DSC_GRADE,
                    MSP.QTD_SEPARAR as QTD_TOTAL,
                    CASE WHEN MSP.QTD_SEPARAR = 0 THEN '100%' 
                         WHEN SMS.TOTAL_SEPARADO = 0 THEN '0%' 
                         WHEN CAST((SMS.TOTAL_SEPARADO/MSP.QTD_SEPARAR) * 100 as NUMBER(6,2)) >= 100 THEN '100%'
                         ELSE CAST((SMS.TOTAL_SEPARADO/MSP.QTD_SEPARAR) * 100 as NUMBER(6,2)) || '%' END PERCENTUAL_SEPARACAO,
                    MS.COD_EXPEDICAO,
                    CASE WHEN MSP.QTD_SEPARAR = 0 THEN 100 WHEN SMS.TOTAL_SEPARADO = 0 THEN 0 ELSE NVL(CAST((SMS.TOTAL_SEPARADO/MSP.QTD_SEPARAR) * 100 as NUMBER(6,2)),0) END PERCENTUAL
                
                FROM MAPA_SEPARACAO MS
                LEFT JOIN (
                    SELECT MSP.COD_MAPA_SEPARACAO, 
                        COD_PRODUTO, 
                        DSC_GRADE, 
                        SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM)- MSP.QTD_CORTADO) as QTD_SEPARAR
                    FROM MAPA_SEPARACAO MS
                      INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    GROUP BY MSP.COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                    SELECT COD_PRODUTO, 
                        DSC_GRADE, 
                        SUM(QTD_SEPARADA * QTD_EMBALAGEM) AS TOTAL_SEPARADO, 
                        COD_MAPA_SEPARACAO
                    FROM SEPARACAO_MAPA_SEPARACAO  
                    GROUP BY  COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) SMS ON SMS.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND SMS.COD_PRODUTO = MSP.COD_PRODUTO AND SMS.DSC_GRADE = MSP.DSC_GRADE 
                WHERE 1 = 1
                $where
                ORDER BY MS.COD_MAPA_SEPARACAO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCaixasByExpedicao($idExpedicao)
    {
        $sql = "SELECT SUM(NUMERO_CAIXAS) NUMERO_CAIXAS, COD_EXPEDICAO, COD_PESSOA
                FROM (
                    SELECT COUNT(NVL(MSC.COD_MAPA_SEPARACAO_EMB_CLIENTE,0)) NUMERO_CAIXAS, MS.COD_EXPEDICAO, MSC.COD_PESSOA
                    FROM MAPA_SEPARACAO_EMB_CLIENTE MSC
                    INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO
                    WHERE MS.COD_EXPEDICAO = $idExpedicao
                    GROUP BY MSC.COD_PESSOA, MS.COD_EXPEDICAO
                UNION ALL
                    SELECT COUNT(NVL(ES.COD_ETIQUETA_SEPARACAO,0)) NUMERO_CAIXAS, E.COD_EXPEDICAO, P.COD_PESSOA
                    FROM ETIQUETA_SEPARACAO ES
                    INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                    INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                    INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE E.COD_EXPEDICAO = $idExpedicao 
                        AND ES.COD_STATUS = ". EtiquetaSeparacao::STATUS_CONFERIDO ."
                    GROUP BY P.COD_PESSOA, E.COD_EXPEDICAO)
                WHERE COD_EXPEDICAO = $idExpedicao
                GROUP BY COD_EXPEDICAO, COD_PESSOA";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $arrayClientes = array();
        foreach ($result as $item) {
            $arrayClientes[$item['COD_PESSOA']] = $item['NUMERO_CAIXAS'];
        }

        return $arrayClientes;

    }

    public function getDetalhamentoSeparacaoByMapa($idMapa) {
        $sql = "SELECT P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       TO_CHAR(SMS.DTH_SEPARACAO,'DD/MM/YYYY HH24:MI:SS') as DTH_SEPARACAO,
                       PES.NOM_PESSOA,
                       NVL(SMS.DSC_LOTE,'-') as DSC_LOTE,
                       SMS.QTD_SEPARADA || ' ' || NVL(PV.DSC_VOLUME, PE.DSC_EMBALAGEM || '(' || SMS.QTD_EMBALAGEM || ')') as EMBALAGEM
                  FROM SEPARACAO_MAPA_SEPARACAO SMS
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = SMS.COD_PRODUTO AND P.DSC_GRADE = SMS.DSC_GRADE
                  LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = SMS.COD_OS
                  LEFT JOIN PESSOA PES ON PES.COD_PESSOA = OS.COD_PESSOA
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = SMS.COD_PRODUTO_EMBALAGEM
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = SMS.COD_PRODUTO_VOLUME
                 WHERE SMS.COD_MAPA_SEPARACAO = $idMapa
                 ORDER BY P.COD_PRODUTO, P.DSC_GRADE, SMS.DTH_SEPARACAO";

        $result =  $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $produtos = [];

        foreach ($result as $r) {

            $k = null;
            if (count($produtos) >0) {
                foreach ($produtos['produtos'] as $key => $p) {
                    if (($p['codProduto'] == $r['COD_PRODUTO']) && ($p['grade']== $r['DSC_GRADE'])) {
                        $k = $key;
                    }
                }
            }

            $separacao = array();
            if (!($k === null)) {
                foreach ($produtos['produtos'][$k]['separacao'] as $sep) {
                    $separacao[] = array(
                        'dthSeparacao' => $sep['dthSeparacao'],
                        'separador' => $sep['separador'],
                        'lote' => $sep['lote'],
                        'embalagem' => $sep['embalagem']
                    );
                }
            }
            $separacao[] = array(
                'dthSeparacao' => $r['DTH_SEPARACAO'],
                'separador' => $r['NOM_PESSOA'],
                'lote' => $r['DSC_LOTE'],
                'embalagem' => $r['EMBALAGEM']
            );

            $dadosProduto = array (
                'codProduto' => $r['COD_PRODUTO'],
                'grade' => $r['DSC_GRADE'],
                'descricao' => $r['DSC_PRODUTO'],
                'separacao' => $separacao
            );

            if ($k === null) {
                $produtos['produtos'][] = $dadosProduto;
            } else {
                $produtos['produtos'][$k] = $dadosProduto;
            }
        }

        return $produtos;
    }
}
