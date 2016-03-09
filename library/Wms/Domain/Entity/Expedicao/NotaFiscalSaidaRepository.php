<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class NotaFiscalSaidaRepository extends EntityRepository
{

    public function save()
    {

    }

    public function getNotaFiscalOuCarga($data)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.numeroNf', 'c.codCargaExterno carga', 'nfs.serieNf', 'nfs.id' , 'pj.cnpj','pes.nome')
            ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->innerJoin('nfsp.pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('nfs.pessoa','pes')
            ->innerJoin('wms:Pessoa\Juridica','pj', 'WITH','pj.id = pes.id');

        if (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
            $sql->andWhere("nfs.numeroNf = $data[notaFiscal]");
        } elseif (isset($data['carga']) && !empty($data['carga'])) {
            $sql->andWhere("c.codCargaExterno = $data[carga]");
        }
        $sql->groupBy('nfs.numeroNf', 'c.codCargaExterno', 'nfs.serieNf', 'nfs.id','pj.cnpj','pes.nome');

        return $sql->getQuery()->getResult();
    }

    public function getQtdProdutoDivergentesByNota($data)
    {
        $idRecebimentoReentrega = $data['id'];

        $SQL = "
            SELECT DISTINCT
                   NFPROD.COD_PRODUTO,
                   NFPROD.DSC_GRADE,
                   P.DSC_PRODUTO,
                   NVL(CONF.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                   NVL(NFPROD.QTD_NOTA,0)  as QTD_NF
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

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

}

