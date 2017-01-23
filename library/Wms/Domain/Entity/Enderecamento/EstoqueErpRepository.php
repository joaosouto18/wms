<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class EstoqueErpRepository extends EntityRepository
{

    public function getProdutosDivergentesByInventario($idInventario) {
        $sql = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               NVL(ERP.ESTOQUE_DISPONIVEL,0) as ESTOQUE_ERP,
               NVL(WMS.QTD,0) as ESTOQUE_WMS
          FROM ESTOQUE_ERP ERP
          FULL OUTER JOIN (SELECT E.COD_PRODUTO,
                                  E.DSC_GRADE, 
                                  MIN(QTD) as QTD
                                  FROM (SELECT E.COD_PRODUTO,
                                               E.DSC_GRADE,
                                               SUM(E.QTD) as QTD,
                                               NVL(E.COD_PRODUTO_VOLUME,0) as ID_VOLUME
                                          FROM ESTOQUE E
                                          GROUP BY E.COD_PRODUTO, E.DSC_GRADE,NVL(E.COD_PRODUTO_VOLUME,0)) E
                                   GROUP BY COD_PRODUTO, DSC_GRADE) WMS
            ON ERP.COD_PRODUTO = WMS.COD_PRODUTO
           AND ERP.DSC_GRADE = WMS.DSC_GRADE
          LEFT JOIN PRODUTO P 
              ON (P.COD_PRODUTO = ERP.COD_PRODUTO AND P.DSC_GRADE = ERP.DSC_GRADE)
              OR (P.COD_PRODUTO = WMS.COD_PRODUTO AND P.DSC_GRADE = WMS.DSC_GRADE)";


        if ($idInventario != null) {
            $sql .= "         
           INNER JOIN (SELECT ICE.COD_PRODUTO,
                            ICE.DSC_GRADE
                       FROM INVENTARIO_ENDERECO IE
                       LEFT JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                      WHERE COD_INVENTARIO = $idInventario AND ICE.CONTAGEM_INVENTARIADA = 1 AND ICE.DIVERGENCIA IS NULL) I
              ON (I.COD_PRODUTO = ERP.COD_PRODUTO AND I.DSC_GRADE = ERP.DSC_GRADE)
              OR (I.COD_PRODUTO = WMS.COD_PRODUTO AND I.DSC_GRADE = WMS.DSC_GRADE)";
        }

        $sql.= " WHERE NVL(WMS.QTD,0) <> NVL(ERP.ESTOQUE_DISPONIVEL,0) 
                 ORDER BY P.COD_PRODUTO, P.DSC_GRADE";

        $result = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

}
