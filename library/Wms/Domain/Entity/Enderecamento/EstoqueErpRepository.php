<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class EstoqueErpRepository extends EntityRepository
{

    public function getProdutosDivergentesByInventario($idInventario,$params) {

        $where = '';
        if ($params['divergencia'] == 'S') {
            $where .= ' AND NVL(WMS.QTD,0) <> NVL(ERP.ESTOQUE_GERENCIAL,0)';
        } elseif ($params['divergencia'] == 'N') {
            $where .= ' AND NVL(WMS.QTD,0) = NVL(ERP.ESTOQUE_GERENCIAL,0)';
        }

        if ($params['tipoDivergencia'] == 'S') {
            $where .= ' AND NVL(WMS.QTD,0) > NVL(ERP.ESTOQUE_GERENCIAL,0)';
        } elseif ($params['tipoDivergencia'] == 'F') {
            $where .= ' AND NVL(WMS.QTD,0) < NVL(ERP.ESTOQUE_GERENCIAL,0)';
        }

        if ($params['estoqueErp'] == 'S') {
            $where .= ' AND ERP.ESTOQUE_GERENCIAL > 0';
        } elseif ($params['estoqueErp'] == 'N') {
            $where .= ' AND ERP.ESTOQUE_GERENCIAL = 0';
        }

        if ($params['estoqueWms'] == 'S') {
            $where .= ' AND WMS.QTD > 0';
        } elseif ($params['estoqueWms'] == 'N') {
            $where .= ' AND WMS.QTD IS NULL';
        }

        if (isset($params['linhaSeparacao']) && !empty($params['linhaSeparacao'])) {
            $where .= " AND P.COD_LINHA_SEPARACAO = $params[linhaSeparacao] ";
        }

        $sql = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               NVL(ERP.ESTOQUE_GERENCIAL,0) as ESTOQUE_ERP,
               NVL(WMS.QTD,0) as ESTOQUE_WMS,
               NVL(WMS.QTD,0) - NVL(ERP.ESTOQUE_GERENCIAL,0) DIVERGENCIA
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
                      WHERE COD_INVENTARIO = $idInventario AND ICE.CONTAGEM_INVENTARIADA = 1 AND ICE.DIVERGENCIA IS NULL
                      GROUP BY ICE.COD_PRODUTO,
                            ICE.DSC_GRADE) I
              ON (I.COD_PRODUTO = ERP.COD_PRODUTO AND I.DSC_GRADE = ERP.DSC_GRADE)
              OR (I.COD_PRODUTO = WMS.COD_PRODUTO AND I.DSC_GRADE = WMS.DSC_GRADE)";
        }

        $sql.= " WHERE 1 = 1
                    $where         
                 ORDER BY P.COD_PRODUTO, P.DSC_GRADE";

        $result = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

}
