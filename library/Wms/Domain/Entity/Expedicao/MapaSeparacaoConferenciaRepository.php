<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoConferenciaRepository extends EntityRepository
{

    public function getProdutosConferir($id)
    {
        $sql = "SELECT ((MSP.QTD_SEPARAR - MSP.QTD_CORTADO) - NVL(SUM(MSC.QTD_CONFERIDA),0)) AS QTD_CONFERIR,
                         MSP.COD_PRODUTO,
                                 MSP.DSC_GRADE,
                                 P.DSC_PRODUTO,
                                 DE.DSC_DEPOSITO_ENDERECO
                    FROM MAPA_SEPARACAO MS
                   LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                   LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                   LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                   LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
                   LEFT JOIN DEPOSITO_ENDERECO DE ON PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO 
                   WHERE MS.COD_EXPEDICAO = $id
                   HAVING (MSP.QTD_SEPARAR - MSP.QTD_CORTADO - NVL(SUM(MSC.QTD_CONFERIDA),0)) > 0
                   GROUP BY MSP.COD_PRODUTO,
                         MSP.DSC_GRADE,
                                     MSP.QTD_SEPARAR,
                                     DE.DSC_DEPOSITO_ENDERECO,
                                     MSP.QTD_CORTADO,
                                     P.DSC_PRODUTO
                                     ORDER BY DE.DSC_DEPOSITO_ENDERECO           
                                     ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


}