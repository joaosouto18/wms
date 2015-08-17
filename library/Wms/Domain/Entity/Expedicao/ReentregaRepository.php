<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class ReentregaRepository extends EntityRepository
{

    public function getItemNotasByExpedicao ($idExpedicao) {
        $SQL = "SELECT NFP.COD_NOTA_FISCAL_SAIDA,
                       NFP.COD_PRODUTO,
                       NFP.DSC_GRADE,
                       NFP.QUANTIDADE
                  FROM REENTREGA R
                  LEFT JOIN NOTA_FISCAL_SAIDA NFS ON NFS.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                  LEFT JOIN NOTA_FISCAL_SAIDA_PRODUTO NFP ON NFP.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
                  LEFT JOIN CARGA C ON C.COD_CARGA = R.COD_CARGA
                 WHERE C.COD_EXPEDICAO = " . $idExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
}