<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class EstoqueErpRepository extends EntityRepository
{

    public function getProdutosDivergentesByInventario($params) {

        $where = '';
        $inventarioComparar = 'A';

        if (isset($params['modeloInventario']) && !empty($params['modeloInventario'])) {
            $inventarioComparar = $params['modeloInventario'];
        }

        $fieldEstoqueERP = 'NVL(ERP.ESTOQUE_GERENCIAL,0)';
        if ($params['deduzirAvaria'] == 'S') {
            $fieldEstoqueERP = "(" . $fieldEstoqueERP . " - NVL(ERP.ESTOQUE_AVARIA,0))";
        }

        if ($params['estoqueErp'] == 'S') {
            $where .= ' AND ERP.ESTOQUE_GERENCIAL > 0';
        } elseif ($params['estoqueErp'] == 'N') {
            $where .= ' AND ERP.ESTOQUE_GERENCIAL = 0';
        }

        if (isset($params['linhaSeparacao']) && !empty($params['linhaSeparacao'])) {
            $where .= " AND P.COD_LINHA_SEPARACAO = $params[linhaSeparacao] ";
        }

        if (isset($params['fabricante']) && !empty($params['fabricante'])) {
            $where .= " AND F.COD_FABRICANTE = $params[fabricante] ";
        }
        $fieldReservaEntrada = "";
        $joinReservaEntrada = "";
        if (!empty($params['considerarReservaEntrada']) && $params['considerarReservaEntrada'] = 'S') {
            $fieldReservaEntrada = " + NVL(RE.QTD, 0)";
            $joinReservaEntrada = " 
            LEFT JOIN (
                    SELECT REP.COD_PRODUTO, REP.DSC_GRADE, (SUM(REP.QTD_RESERVADA) / NVL(P.NUM_VOLUMES, 1) )* -1 QTD FROM RESERVA_ESTOQUE R
                INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = R.COD_RESERVA_ESTOQUE
                INNER JOIN RESERVA_ESTOQUE_ENDERECAMENTO REE on R.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = REP.COD_PRODUTO AND P.DSC_GRADE = REP.DSC_GRADE
                     WHERE R.TIPO_RESERVA = 'E' AND R.IND_ATENDIDA = 'N'
                  GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, NVL(P.NUM_VOLUMES, 1)
            ) RE ON RE.COD_PRODUTO = ERP.COD_PRODUTO AND RE.DSC_GRADE = ERP.DSC_GRADE";
        }

        $fieldReservaSaida = "";
        $joinReservaSaida = "";
        if (!empty($params['considerarReservaSaida']) && $params['considerarReservaSaida'] = 'S') {
            $fieldReservaSaida = " - NVL(RS.QTD, 0)";
            $joinReservaSaida = "
            LEFT JOIN (
                    SELECT REP.COD_PRODUTO, REP.DSC_GRADE, (SUM(REP.QTD_RESERVADA) / NVL(P.NUM_VOLUMES, 1) )* -1 QTD FROM RESERVA_ESTOQUE R
                INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = R.COD_RESERVA_ESTOQUE
                INNER JOIN RESERVA_ESTOQUE_EXPEDICAO REE on R.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = REP.COD_PRODUTO AND P.DSC_GRADE = REP.DSC_GRADE
                     WHERE R.TIPO_RESERVA = 'S' AND R.IND_ATENDIDA = 'N'
                  GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, NVL(P.NUM_VOLUMES, 1)
            ) RS ON RS.COD_PRODUTO = ERP.COD_PRODUTO AND RS.DSC_GRADE = ERP.DSC_GRADE";
        }

        if ($params['divergencia'] == 'S') {
            $where .= " AND NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) <> $fieldEstoqueERP";
        } elseif ($params['divergencia'] == 'N') {
            $where .= " AND NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) = $fieldEstoqueERP";
        }

        if ($params['tipoDivergencia'] == 'S') {
            $where .= " AND NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) > $fieldEstoqueERP";
        } elseif ($params['tipoDivergencia'] == 'F') {
            $where .= " AND NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) < $fieldEstoqueERP";
        }


        if ($params['estoqueWms'] == 'S') {
            $where .= " AND (WMS.QTD $fieldReservaEntrada $fieldReservaSaida) > 0";
        } elseif ($params['estoqueWms'] == 'N') {
            $where .= " AND (WMS.QTD $fieldReservaEntrada $fieldReservaSaida) IS NULL";
        }

        $sql = "
        SELECT P.COD_PRODUTO,
               P.DSC_GRADE,
               P.DSC_PRODUTO,
               $fieldEstoqueERP as ESTOQUE_ERP,
               NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) as ESTOQUE_WMS,
               NVL(ERP.ESTOQUE_AVARIA,0) as ESTOQUE_AVARIA,
               NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) - $fieldEstoqueERP DIVERGENCIA,
               NVL($fieldEstoqueERP * ERP.VLR_ESTOQUE_UNIT,0) as VLR_ESTOQUE_ERP,
               NVL(NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) * ERP.VLR_ESTOQUE_UNIT,0) as VLR_ESTOQUE_WMS,
               NVL((NVL((WMS.QTD $fieldReservaEntrada $fieldReservaSaida),0) - $fieldEstoqueERP) * ERP.VLR_ESTOQUE_UNIT,0) as VLR_DIVERGENCIA,
               F.COD_FABRICANTE,
               F.NOM_FABRICANTE as FABRICANTE 
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
              OR (P.COD_PRODUTO = WMS.COD_PRODUTO AND P.DSC_GRADE = WMS.DSC_GRADE)
          INNER JOIN FABRICANTE F 
              ON F.COD_FABRICANTE = P.COD_FABRICANTE";

        if (!empty($params['inventario'])) {
            if ($inventarioComparar == 'A') {
                $sql .= " INNER JOIN (SELECT ICE.COD_PRODUTO,
                                             ICE.DSC_GRADE
                                        FROM INVENTARIO_ENDERECO IE
                                   LEFT JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                                       WHERE COD_INVENTARIO = $params[inventario] AND ICE.CONTAGEM_INVENTARIADA = 1 AND ICE.DIVERGENCIA IS NULL
                                       GROUP BY ICE.COD_PRODUTO,
                                         ICE.DSC_GRADE) I
                             ON (I.COD_PRODUTO = ERP.COD_PRODUTO AND I.DSC_GRADE = ERP.DSC_GRADE)
                             OR (I.COD_PRODUTO = WMS.COD_PRODUTO AND I.DSC_GRADE = WMS.DSC_GRADE)";
            } else {
                $sql .= " INNER JOIN (SELECT DISTINCT
                                             ICEP.COD_PRODUTO,
                                             ICEP.DSC_GRADE
                                        FROM INVENTARIO_NOVO INVN
                                       INNER JOIN INVENTARIO_ENDERECO_NOVO IEN ON IEN.COD_INVENTARIO = INVN.COD_INVENTARIO
                                       INNER JOIN INVENTARIO_CONT_END ICE ON IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO
                                       INNER JOIN INVENTARIO_CONT_END_PROD ICEP ON ICE.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
                                       WHERE INVN.COD_INVENTARIO = $params[inventario]) I
                             ON (I.COD_PRODUTO = ERP.COD_PRODUTO AND I.DSC_GRADE = ERP.DSC_GRADE)
                             OR (I.COD_PRODUTO = WMS.COD_PRODUTO AND I.DSC_GRADE = WMS.DSC_GRADE)";
            }
        }

        $sql.= $joinReservaEntrada;
        $sql.= $joinReservaSaida;

        $sql.= " WHERE 1 = 1
                    $where         
                 ORDER BY P.DSC_PRODUTO, P.COD_PRODUTO, P.DSC_GRADE";

        $result = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $key => $value) {
            $result[$key]['id'] = $value['COD_PRODUTO'];
        }
        return $result;
    }

}
