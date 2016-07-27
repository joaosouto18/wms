<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;

class PaleteProdutoRepository extends EntityRepository
{
    public function getQtdTotalEnderecadaByRecebimento($idRecebimento, $codProduto, $grade)
    {
        $sql = "SELECT NVL(SUM(RE.QTD_CONFERIDA), SUM(RV.QTD_CONFERIDA)) QTD
                    FROM RECEBIMENTO R
                    LEFT JOIN RECEBIMENTO_EMBALAGEM RE ON RE.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                    LEFT JOIN RECEBIMENTO_VOLUME RV ON RV.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                    LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                    LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE OR P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                    WHERE R.COD_RECEBIMENTO = $idRecebimento AND P.COD_PRODUTO = '$codProduto' AND P.DSC_GRADE = '$grade'";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutoByUma($uma)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('prod')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->innerJoin('wms:Produto', 'prod', 'WITH', 'prod.id = pp.codProduto AND prod.grade = pp.grade')
//            ->leftJoin('wms:Produto\Embalagem', 'pe' ,'WITH', 'pe.codProduto = prod.id AND pe.grade = prod.grade')
//            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'pv.codProduto = prod.id AND pv.grade = prod.grade')
            ->where("p.id = $uma");

        return $sql->getQuery()->getResult();

    }


}
