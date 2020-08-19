<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Util\Coletor as ColetorUtil;

class NotaFiscalSaidaRepository extends EntityRepository {

    /**
     * @param $cnpjEmitente
     * @param $numeroNf
     * @param $serieNF
     * @throws \Exception
     */
    public function cancelarNota($cnpjEmitente, $numeroNf, $serieNF)
    {
        try {
            $this->_em->beginTransaction();
            $pessoaJuridicaRepository = $this->_em->getRepository('wms:Pessoa\Juridica');

            $pessoaEn = $pessoaJuridicaRepository->findOneBy(['cnpj' => $cnpjEmitente]);

            if (empty($pessoaEn)) {
                throw new \Exception("Emitente não encontrado para o cnpj " . $cnpjEmitente);
            }

            /** @var NotaFiscalSaida $notaFiscalEn */
            $notaFiscalEn = $this->findOneBy(['numeroNf' => $numeroNf, 'pessoa' => $pessoaEn, 'serieNf' => $serieNF]);

            if (empty($notaFiscalEn)) {
                throw new \Exception('Nota Fiscal ' . $numeroNf . " / " . $serieNF . " não encontrada para o CNPJ $cnpjEmitente");
            }

            $statusEn = $this->_em->getReference('wms:Util\Sigla', NotaFiscalSaida::NOTA_FISCAL_CANCELADA);
            $notaFiscalEn->setStatus($statusEn);
            $notaFiscalEn->setDataCancelamento(new \DateTime());

            /** @var NotaFiscalSaidaPedido[] $pedidosNF */
            $pedidosNF = $this->_em->getRepository(NotaFiscalSaidaPedido::class)->findBy(['notaFiscalSaida' => $notaFiscalEn]);

            foreach ($pedidosNF as $pedidoNF) {
                $pedido = $pedidoNF->getPedido();
                $pedido->setFaturado('N');
                $this->_em->persist($pedido);
            }

            $this->_em->persist($notaFiscalEn);
            $this->_em->flush();
            $this->_em->commit();

        } catch (\Exception $e) {
            $this->_em->rollback();
            throw $e;
        }
    }

    public function atualizaStatusNota($codNota) {

        $status = $this->getEntityManager()->getReference('wms:Util\Sigla', NotaFiscalSaida::NOTA_FISCAL_EMITIDA);
        $notaFiscalSaida = $this->findOneBy(array('numeroNf' => $codNota));
        if (is_object($notaFiscalSaida)) {
            $notaFiscalSaida->setStatus($status);
            $this->_em->flush();
        }
    }

    public function getNotaFiscalSaida($data) {

        if ($this->getSystemParameterValue('IND_UTILIZA_INTEGRACAO_NF_SAIDA') == 'S') {
            $options = array();

            if (isset($data['pedido']) && !empty($data['pedido'])) {
                $options[] = $data['pedido'];
            } elseif (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
                $options[] = $data['notaFiscal'];
            } elseif (isset($data['carga']) && !empty($data['carga'])) {
                $options[] = $data['carga'];
            } else
                $options[] = 0;

            $idIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_NOTA_FISCAL_SAIDA');

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            $acaoEn = $acaoIntRepo->find($idIntegracao);
            $acaoIntRepo->processaAcao($acaoEn, $options, 'E', "P", null, 612);
        }

        $sql = $this->getEntityManager()->createQueryBuilder()
                ->select('DISTINCT nfs.numeroNf', 'c.codCargaExterno carga', 'nfs.serieNf', 'nfs.id', 'nfs.chaveAcesso', 'pj.cnpj', 'pf.cpf', 'pes.nome')
                ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
                ->innerJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
                ->innerJoin('nfsp.pedido', 'p')
                ->innerJoin('p.carga', 'c')
                ->innerJoin('nfs.pessoa', 'pes')
                ->leftJoin('wms:Pessoa\Juridica', 'pj', 'WITH', 'pj.id = pes.id')
                ->leftJoin('wms:Pessoa\Fisica', 'pf', 'WITH', 'pf.id = pes.id');

        if (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
            $sql->andWhere("nfs.numeroNf IN (".$data['notaFiscal'].")");
        } elseif (isset($data['carga']) && !empty($data['carga'])) {
            $sql->andWhere("c.codCargaExterno = ('". $data['carga'] ."')");
        } elseif (isset($data['pedido']) && !empty($data['pedido'])) {
            $sql->andWhere("p.codExterno = ('". $data['pedido'] ."')");
        }

        if (isset($data['codEtiqueta']) && !empty($data['codEtiqueta'])) {
            $codBarras = ColetorUtil::retiraDigitoIdentificador($data['codEtiqueta']);
            $sql->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'p.id = etq.pedido');
            $sql->andWhere("etq.id = $codBarras");
        }
        $sql->groupBy('nfs.numeroNf', 'c.codCargaExterno', 'nfs.serieNf', 'nfs.id', 'nfs.chaveAcesso', 'pj.cnpj', 'pf.cpf', 'pes.nome');

        return $sql->getQuery()->getResult();
    }

    function nvl(&$var, $default = "")
    {
        return isset($var) ? $var : $default;
    }

    public function getQtdProdutoDivergentesByNota($data) {
        $idRecebimentoReentrega = $data['id'];

        $SQL = "
            SELECT DISTINCT
                   NFPROD.COD_PRODUTO,
                   NFPROD.DSC_GRADE,
                   P.DSC_PRODUTO
              FROM (SELECT COD_PRODUTO, DSC_GRADE, SUM(QUANTIDADE) as QTD_NOTA, COD_RECEBIMENTO_REENTREGA
                      FROM RECEBIMENTO_REENTREGA_NF R
                      LEFT JOIN NOTA_FISCAL_SAIDA_PRODUTO NFSP ON NFSP.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL
                     GROUP BY COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO_REENTREGA) NFPROD
              LEFT JOIN (SELECT CONF.COD_PRODUTO, CONF.DSC_GRADE, MAXC.COD_PRODUTO_VOLUME, CONF.COD_RECEBIMENTO_REENTREGA, SUM(NVL(CONF.QTD_CONFERIDA,0)) as QTD_CONFERIDA
                           FROM (SELECT CONF.COD_PRODUTO, CONF.DSC_GRADE, CONF.COD_RECEBIMENTO_REENTREGA,
                                        NVL(CONF.COD_PRODUTO_VOLUME,0) as COD_PRODUTO_VOLUME,
                                        MAX(NVL(CONF.NUM_CONFERENCIA,0)) as NUM_CONFERENCIA
                                  FROM (SELECT DISTINCT C.COD_PRODUTO, C.DSC_GRADE, NVL(PV.COD_PRODUTO_VOLUME,0) as COD_PRODUTO_VOLUME, C.COD_RECEBIMENTO_REENTREGA
                                          FROM CONF_RECEB_REENTREGA C
                                          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = C.COD_PRODUTO AND C.DSC_GRADE = PV.DSC_GRADE
                                         WHERE C.COD_RECEBIMENTO_REENTREGA = $idRecebimentoReentrega) PROD
                                  LEFT JOIN CONF_RECEB_REENTREGA CONF
                                    ON CONF.COD_PRODUTO = PROD.COD_PRODUTO
                                   AND CONF.DSC_GRADE = PROD.DSC_GRADE
                                   AND NVL(CONF.COD_PRODUTO_VOLUME,0) = PROD.COD_PRODUTO_VOLUME
                                   AND CONF.COD_RECEBIMENTO_REENTREGA = PROD.COD_RECEBIMENTO_REENTREGA
                                 GROUP BY CONF.COD_PRODUTO, CONF.DSC_GRADE, CONF.COD_RECEBIMENTO_REENTREGA, CONF.COD_PRODUTO_VOLUME) MAXC
                           LEFT JOIN CONF_RECEB_REENTREGA CONF
                             ON CONF.NUM_CONFERENCIA = MAXC.NUM_CONFERENCIA
                            AND CONF.COD_PRODUTO = MAXC.COD_PRODUTO
                            AND CONF.DSC_GRADE = MAXC.DSC_GRADE
                            AND NVL(CONF.COD_PRODUTO_VOLUME,0) = MAXC.COD_PRODUTO_VOLUME
                            AND CONF.COD_RECEBIMENTO_REENTREGA = MAXC.COD_RECEBIMENTO_REENTREGA
                          GROUP BY CONF.COD_PRODUTO, CONF.DSC_GRADE, MAXC.COD_PRODUTO_VOLUME, CONF.COD_RECEBIMENTO_REENTREGA) CONF
                ON CONF.COD_PRODUTO = NFPROD.COD_PRODUTO
               AND CONF.DSC_GRADE = NFPROD.DSC_GRADE
               AND CONF.COD_RECEBIMENTO_REENTREGA = NFPROD.COD_RECEBIMENTO_REENTREGA
              LEFT JOIN PRODUTO P ON P.COD_PRODUTO = NFPROD.COD_PRODUTO AND P.DSC_GRADE = NFPROD.DSC_GRADE
             WHERE NFPROD.COD_RECEBIMENTO_REENTREGA = $idRecebimentoReentrega
               AND NVL(CONF.QTD_CONFERIDA,0) <> NVL(NFPROD.QTD_NOTA,0)
        ";

        $SQL = "SELECT DISTINCT
                   CONF.COD_PRODUTO_VOLUME,
                   CONF.DSC_VOLUME,
                   NFPROD.COD_PRODUTO,
                   NFPROD.DSC_GRADE,
                   P.DSC_PRODUTO,
                   NVL(CONF.QTD_CONFERIDA,0) AS QTD_CONFERIDA,
                   NVL(NFPROD.QTD_NOTA,0)  AS QTD_NF
              FROM (SELECT COD_PRODUTO, DSC_GRADE, SUM(QUANTIDADE) AS QTD_NOTA, COD_RECEBIMENTO_REENTREGA
                      FROM RECEBIMENTO_REENTREGA_NF R
                      LEFT JOIN NOTA_FISCAL_SAIDA_PRODUTO NFSP ON NFSP.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL
                     GROUP BY COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO_REENTREGA) NFPROD
              LEFT JOIN (SELECT MAXC.COD_PRODUTO, MAXC.DSC_GRADE, MAXC.DSC_VOLUME, MAXC.COD_PRODUTO_VOLUME, MAXC.COD_RECEBIMENTO_REENTREGA, SUM(NVL(CONF.QTD_CONFERIDA,0)) AS QTD_CONFERIDA

                           FROM (SELECT PROD.COD_PRODUTO, PROD.DSC_GRADE, PROD.COD_RECEBIMENTO_REENTREGA,
                                NVL(PROD.COD_PRODUTO_VOLUME,0) AS COD_PRODUTO_VOLUME,
                                NVL(PROD.DSC_VOLUME,0) as DSC_VOLUME,
                                MAX(NVL(CONF.NUM_CONFERENCIA,0)) AS NUM_CONFERENCIA
                          FROM (SELECT DISTINCT C.COD_PRODUTO, C.DSC_GRADE, NVL(PV.COD_PRODUTO_VOLUME,0) AS COD_PRODUTO_VOLUME, NVL(PV.DSC_VOLUME,0) as DSC_VOLUME, C.COD_RECEBIMENTO_REENTREGA
                                  FROM CONF_RECEB_REENTREGA C
                                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = C.COD_PRODUTO AND C.DSC_GRADE = PV.DSC_GRADE
                                 WHERE C.COD_RECEBIMENTO_REENTREGA = $idRecebimentoReentrega) PROD
                          LEFT JOIN CONF_RECEB_REENTREGA CONF
                            ON CONF.COD_PRODUTO = PROD.COD_PRODUTO
                           AND CONF.DSC_GRADE = PROD.DSC_GRADE
                           AND NVL(CONF.COD_PRODUTO_VOLUME,0) = PROD.COD_PRODUTO_VOLUME
                           AND CONF.COD_RECEBIMENTO_REENTREGA = PROD.COD_RECEBIMENTO_REENTREGA
                         GROUP BY PROD.COD_PRODUTO, PROD.DSC_GRADE, PROD.DSC_VOLUME, PROD.COD_RECEBIMENTO_REENTREGA, CONF.COD_PRODUTO_VOLUME, PROD.COD_PRODUTO_VOLUME) MAXC

                           LEFT JOIN CONF_RECEB_REENTREGA CONF
                             ON CONF.NUM_CONFERENCIA = MAXC.NUM_CONFERENCIA
                            AND CONF.COD_PRODUTO = MAXC.COD_PRODUTO
                            AND CONF.DSC_GRADE = MAXC.DSC_GRADE
                            AND NVL(CONF.COD_PRODUTO_VOLUME,0) = MAXC.COD_PRODUTO_VOLUME
                            AND CONF.COD_RECEBIMENTO_REENTREGA = MAXC.COD_RECEBIMENTO_REENTREGA
                          GROUP BY MAXC.COD_PRODUTO, MAXC.DSC_VOLUME, MAXC.DSC_GRADE, MAXC.COD_PRODUTO_VOLUME, MAXC.COD_RECEBIMENTO_REENTREGA) CONF
                ON CONF.COD_PRODUTO = NFPROD.COD_PRODUTO
               AND CONF.DSC_GRADE = NFPROD.DSC_GRADE
               AND CONF.COD_RECEBIMENTO_REENTREGA = NFPROD.COD_RECEBIMENTO_REENTREGA
              LEFT JOIN PRODUTO P ON P.COD_PRODUTO = NFPROD.COD_PRODUTO AND P.DSC_GRADE = NFPROD.DSC_GRADE
             WHERE NFPROD.COD_RECEBIMENTO_REENTREGA = $idRecebimentoReentrega
               AND NVL(CONF.QTD_CONFERIDA,0) <> NVL(NFPROD.QTD_NOTA,0)
               ORDER BY NFPROD.COD_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

}
