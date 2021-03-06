<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class ReentregaRepository extends EntityRepository {

    public function getReentregasByExpedicao($idExpedicao, $pendentesImpressao = true) {
        $SQL = "SELECT NFS.COD_NOTA_FISCAL_SAIDA,
                       R.COD_REENTREGA
                  FROM REENTREGA R
                  LEFT JOIN NOTA_FISCAL_SAIDA NFS ON NFS.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                  LEFT JOIN CARGA C ON C.COD_CARGA = R.COD_CARGA
                 WHERE 1 = 1
                   AND C.COD_EXPEDICAO = " . $idExpedicao;

        if ($pendentesImpressao == true) {
            $SQL .= " AND R.IND_ETIQUETA_MAPA_GERADO = 'N' ";
        }
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getItemNotasByExpedicao($idExpedicao) {
        $SQL = "SELECT NFP.COD_NOTA_FISCAL_SAIDA,
                       NFP.COD_PRODUTO,
                       NFP.DSC_GRADE,
                       NFP.QUANTIDADE,
                       R.COD_REENTREGA
                  FROM REENTREGA R
                  LEFT JOIN NOTA_FISCAL_SAIDA NFS ON NFS.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                  LEFT JOIN NOTA_FISCAL_SAIDA_PRODUTO NFP ON NFP.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
                  LEFT JOIN CARGA C ON C.COD_CARGA = R.COD_CARGA
                 WHERE 1 = 1
                   AND R.IND_ETIQUETA_MAPA_GERADO = 'N'
                   AND C.COD_EXPEDICAO = " . $idExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function removeReentrega($codCarga) {
        $NotaFiscalSaidaAndamentoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaidaAndamento');
        $reentrega = $this->findOneBy(array('codCarga' => $codCarga));
        if (is_object($reentrega)) {
            $NotaFiscalSaidaAndamentoRepository->removeNFSaidaAndamento($reentrega->getNotaFiscalSaida()->getId());
            $this->getEntityManager()->remove($reentrega);
            $this->getEntityManager()->flush();
        }
    }

}
