<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoConferenciaRepository extends EntityRepository
{

    public function getProdutosConferirByExpedicao($id)
    {

         $sql = " SELECT M.COD_MAPA_SEPARACAO,
                         M.COD_PRODUTO,
                         M.DSC_GRADE,
                         P.DSC_PRODUTO,
                         M.QTD_SEPARAR,
                         M.QTD_SEPARAR - NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIR,
                         NVL(MIN(PE.COD_BARRAS), MIN(PV.COD_BARRAS)) as COD_BARRAS,
                         DE.DSC_DEPOSITO_ENDERECO,
                         LS.DSC_LINHA_SEPARACAO
                    FROM (SELECT M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MP.QTD_EMBALAGEM * MP.QTD_SEPARAR) - SUM(MP.QTD_CORTADO) as QTD_SEPARAR
                            FROM MAPA_SEPARACAO_PRODUTO MP
                            LEFT JOIN MAPA_SEPARACAO M ON M.COD_MAPA_SEPARACAO = MP.COD_MAPA_SEPARACAO
                           WHERE MP.IND_CONFERIDO = 'N'
                           GROUP BY M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0)) M
               LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) as VOLUME, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA
                            FROM MAPA_SEPARACAO_CONFERENCIA
                           WHERE IND_CONFERENCIA_FECHADA = 'N'
                           GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0)) C
                      ON M.COD_MAPA_SEPARACAO = C.COD_MAPA_SEPARACAO
                     AND M.COD_PRODUTO = C.COD_PRODUTO
                     AND M.DSC_GRADE = C.DSC_GRADE
                     AND M.VOLUME = C.VOLUME
                LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP
                  ON MSP.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                 AND MSP.COD_PRODUTO = M.COD_PRODUTO
                 AND MSP.DSC_GRADE = M.DSC_GRADE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO P ON P.COD_PRODUTO = M.COD_PRODUTO AND P.DSC_GRADE = M.DSC_GRADE
                LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
              WHERE M.COD_EXPEDICAO = $id
                AND NVL(C.QTD_CONFERIDA,0) < M.QTD_SEPARAR
                GROUP BY M.COD_MAPA_SEPARACAO,
                         M.COD_PRODUTO,
                         M.DSC_GRADE,
                         P.DSC_PRODUTO,
                         M.QTD_SEPARAR,
                         C.QTD_CONFERIDA,
                         DE.DSC_DEPOSITO_ENDERECO,
                         LS.DSC_LINHA_SEPARACAO
            ORDER BY COD_MAPA_SEPARACAO, M.COD_PRODUTO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferirByMapa($id)
    {

        $sql = "SELECT P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO, PE.COD_BARRAS, (MSP.QTD_SEPARAR - MSP.QTD_CORTADO) AS QTD_SEPARAR, (MSP.QTD_SEPARAR - MSP.QTD_CORTADO) - NVL(MSCE.QTD_CONFERIDA, MSCV.QTD_CONFERIDA) AS QTD_CONFERIR, DE.DSC_DEPOSITO_ENDERECO
                    FROM MAPA_SEPARACAO MS
                    LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    LEFT JOIN (
                        SELECT MSC.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE, SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA
                        FROM MAPA_SEPARACAO_CONFERENCIA MSC
                        WHERE MSC.COD_MAPA_SEPARACAO = $id AND MSC.COD_PRODUTO_VOLUME IS NULL
                        GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO
                        ) MSCE ON MSCE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSCE.COD_PRODUTO = MSP.COD_PRODUTO AND MSCE.DSC_GRADE = MSP.DSC_GRADE
                    LEFT JOIN (
                        SELECT MSC.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.QTD_CONFERIDA
                        FROM MAPA_SEPARACAO_CONFERENCIA MSC
                        WHERE MSC.COD_MAPA_SEPARACAO = $id AND MSC.COD_PRODUTO_EMBALAGEM IS NULL
                        GROUP BY MSC.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.QTD_CONFERIDA
                        ) MSCV ON MSCV.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSCV.COD_PRODUTO = MSP.COD_PRODUTO AND MSCV.DSC_GRADE = MSP.DSC_GRADE
                    LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                    LEFT JOIN (
                        SELECT P.COD_PRODUTO, P.DSC_GRADE, NVL(E.COD_PRODUTO_EMBALAGEM, V.COD_PRODUTO_VOLUME) PRODUTO_EMBALAGEM, NVL(E.COD_BARRAS, V.COD_BARRAS) COD_BARRAS
                        FROM PRODUTO P
                        LEFT JOIN PRODUTO_EMBALAGEM E ON E.COD_PRODUTO = P.COD_PRODUTO AND E.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN PRODUTO_VOLUME V ON V.COD_PRODUTO = P.COD_PRODUTO AND V.DSC_GRADE = P.DSC_GRADE
                        GROUP BY P.COD_PRODUTO, P.DSC_GRADE, E.COD_PRODUTO_EMBALAGEM, V.COD_PRODUTO_VOLUME, E.COD_BARRAS, V.COD_BARRAS
                        ) PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE AND (PE.PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM OR PE.PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_VOLUME)
                    LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = MSP.COD_DEPOSITO_ENDERECO
                WHERE MS.COD_MAPA_SEPARACAO = $id
                GROUP BY P.COD_PRODUTO, P.DSC_GRADE, MSP.QTD_SEPARAR, MSP.QTD_CORTADO, P.DSC_PRODUTO, DE.DSC_DEPOSITO_ENDERECO, PE.COD_BARRAS, MSCE.QTD_CONFERIDA, MSCV.QTD_CONFERIDA
                HAVING (MSP.QTD_SEPARAR - MSP.QTD_CORTADO) - NVL(MSCE.QTD_CONFERIDA, MSCV.QTD_CONFERIDA) > 0
                ORDER BY P.COD_PRODUTO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


}