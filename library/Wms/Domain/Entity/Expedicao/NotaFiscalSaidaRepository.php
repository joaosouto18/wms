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
            ->select('nfs.numeroNf', 'c.id carga', 'nfs.serieNf', 'nfs.id')
            ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->innerJoin('nfsp.pedido', 'p')
            ->innerJoin('p.carga', 'c');
        if (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
            $sql->andWhere("nfs.numeroNf = $data[notaFiscal]");
        } elseif (isset($data['carga']) && !empty($data['carga'])) {
            $sql->andWhere("c.id = $data[carga]");
        }
        $sql->groupBy('nfs.numeroNf', 'c.id', 'nfs.serieNf', 'nfs.id');

        return $sql->getQuery()->getResult();
    }

    public function getQtdProdutoByNota($data)
    {
        $sql = "SELECT NFS.NUMERO_NOTA, P.COD_PRODUTO, P.DSC_GRADE, NVL(PV.COD_BARRAS, PE.COD_BARRAS) COD_BARRAS_NOTA, NFSP.QUANTIDADE - SUM(NVL(QTD_CONFERIDA, 0)) QTD_TOTAL
                    FROM NOTA_FISCAL_SAIDA NFS
                    INNER JOIN NOTA_FISCAL_SAIDA_PRODUTO NFSP ON NFSP.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
                    INNER JOIN RECEBIMENTO_REENTREGA_NF RRNF ON RRNF.COD_NOTA_FISCAL = NFSP.COD_NOTA_FISCAL_SAIDA
                    INNER JOIN PRODUTO P ON P.COD_PRODUTO = NFSP.COD_PRODUTO AND P.DSC_GRADE = NFSP.DSC_GRADE
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                    LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                    LEFT JOIN (
                          SELECT SUM(CFR.QTD_CONFERIDA) QTD_CONFERIDA, MAX(CFR.NUM_CONFERENCIA), P.COD_PRODUTO, P.DSC_GRADE, NVL(PV.COD_BARRAS, PE.COD_BARRAS) COD_BARRAS_CONFERIDO
                          FROM RECEBIMENTO_REENTREGA RR
                          INNER JOIN CONF_RECEB_REENTREGA CFR ON RR.COD_RECEBIMENTO_REENTREGA = CFR.COD_RECEBIMENTO_REENTREGA
                          INNER JOIN PRODUTO P ON P.COD_PRODUTO = CFR.COD_PRODUTO AND P.DSC_GRADE = CFR.DSC_GRADE
                          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = CFR.COD_PRODUTO_VOLUME
                          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = CFR.COD_PRODUTO_EMBALAGEM
                          WHERE RR.COD_RECEBIMENTO_REENTREGA = $data[id]
                          GROUP BY P.COD_PRODUTO, P.DSC_GRADE, PV.COD_BARRAS, PE.COD_BARRAS
                        ) CONF ON CONF.COD_BARRAS_CONFERIDO = PV.COD_BARRAS OR CONF.COD_BARRAS_CONFERIDO = PE.COD_BARRAS
                    WHERE RRNF.COD_RECEBIMENTO_REENTREGA = $data[id]
                    GROUP BY NFS.NUMERO_NOTA, P.COD_PRODUTO, P.DSC_GRADE, PV.COD_BARRAS, PE.COD_BARRAS, NFSP.QUANTIDADE";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

}

