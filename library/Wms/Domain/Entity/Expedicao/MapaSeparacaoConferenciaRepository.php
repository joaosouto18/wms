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
                         LS.DSC_LINHA_SEPARACAO,
                         M.DSC_QUEBRA
                    FROM (SELECT M.COD_EXPEDICAO, M.DSC_QUEBRA, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MP.QTD_EMBALAGEM * MP.QTD_SEPARAR) - SUM(MP.QTD_CORTADO) as QTD_SEPARAR
                            FROM MAPA_SEPARACAO_PRODUTO MP
                            LEFT JOIN MAPA_SEPARACAO M ON M.COD_MAPA_SEPARACAO = MP.COD_MAPA_SEPARACAO
                           WHERE MP.IND_CONFERIDO = 'N'
                           GROUP BY M.COD_EXPEDICAO, M.DSC_QUEBRA, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0)) M
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
                LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR DE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
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
                         LS.DSC_LINHA_SEPARACAO,
                         M.DSC_QUEBRA
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

    public function getConferidosByExpedicao($idExpedicao)
    {
        $sql = "SELECT
                    SUM(PP.QUANTIDADE_CONFERIDA / CONF.QTD_EMBALAGEM) / COUNT(DISTINCT CONF.COD_MAPA_SEPARACAO_CONFERENCIA) QUANTIDADE_CONFERIDA,
                    NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) DESCRICAO_EMBALAGEM,
                    PP.SEQUENCIA,
                    PROD.COD_PRODUTO,
                    PROD.DSC_GRADE,
                    PROD.DSC_PRODUTO,
                    LS.DSC_LINHA_SEPARACAO,
                    E.DTH_INICIO,
                    PP.DSC_PLACA_CARGA
                          FROM MAPA_SEPARACAO_CONFERENCIA CONF
                          INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                          INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                          LEFT JOIN (
                                      SELECT E.COD_EXPEDICAO, C.DSC_PLACA_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) QUANTIDADE_CONFERIDA
                                        FROM EXPEDICAO E
                                        INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                                        INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                                        INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                                        INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                                        INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PP.COD_PRODUTO AND MSC.DSC_GRADE = PP.DSC_GRADE
                                      WHERE E.COD_EXPEDICAO = $idExpedicao AND MSC.COD_MAPA_SEPARACAO_EMBALADO IS NULL
                                      GROUP BY E.COD_EXPEDICAO, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, C.DSC_PLACA_CARGA, PP.COD_PEDIDO_PRODUTO, PP.QUANTIDADE, PP.QTD_CORTADA
                                    ) PP ON PP.COD_EXPEDICAO = E.COD_EXPEDICAO AND PP.COD_PRODUTO = CONF.COD_PRODUTO AND PP.DSC_GRADE = CONF.DSC_GRADE
                          INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                          INNER JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = CONF.COD_PRODUTO_EMBALAGEM
                          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = CONF.COD_PRODUTO_VOLUME
                    WHERE MS.COD_EXPEDICAO = $idExpedicao AND CONF.COD_MAPA_SEPARACAO_EMBALADO IS NULL
                    GROUP BY
                    PE.DSC_EMBALAGEM,
                    PV.DSC_VOLUME,
                    PP.SEQUENCIA,
                    PROD.COD_PRODUTO,
                    PROD.DSC_GRADE,
                    PROD.DSC_PRODUTO,
                    LS.COD_LINHA_SEPARACAO,
                    LS.DSC_LINHA_SEPARACAO,
                    E.DTH_INICIO,
                    PP.DSC_PLACA_CARGA,
                    PE.QTD_EMBALAGEM
                    ORDER BY LS.COD_LINHA_SEPARACAO, PP.SEQUENCIA, PROD.COD_PRODUTO, PE.QTD_EMBALAGEM";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEmbaladosConferidosByExpedicao($idExpedicao)
    {
        $sql = "SELECT  COUNT(DISTINCT MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE) QUANTIDADE_CONFERIDA, PP.SEQUENCIA, C.COD_CARGA_EXTERNO, P.NOM_PESSOA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, MS.DSC_QUEBRA, E.DTH_INICIO, P.COD_PESSOA, MS.COD_MAPA_SEPARACAO
                    FROM MAPA_SEPARACAO_CONFERENCIA CONF
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSE ON MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = CONF.COD_MAPA_SEPARACAO_EMBALADO
                    INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                    INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    LEFT JOIN (
                          SELECT E.COD_EXPEDICAO, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) QUANTIDADE_CONFERIDA, P.COD_PESSOA
                          FROM EXPEDICAO E
                          INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                          INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                          INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                          WHERE E.COD_EXPEDICAO = $idExpedicao
                          GROUP BY E.COD_EXPEDICAO, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, P.COD_PESSOA
                          ) PP ON PP.COD_EXPEDICAO = E.COD_EXPEDICAO AND PP.COD_PRODUTO = CONF.COD_PRODUTO AND PP.DSC_GRADE = CONF.DSC_GRADE AND PP.COD_PESSOA = MSE.COD_PESSOA AND PP.COD_PESSOA = CONF.COD_PESSOA
                    INNER JOIN CARGA C ON PP.COD_CARGA = C.COD_CARGA
                    INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                    INNER JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = CONF.COD_PESSOA AND P.COD_PESSOA = MSE.COD_PESSOA
                    WHERE MS.COD_EXPEDICAO = $idExpedicao
                    GROUP BY PP.SEQUENCIA, P.NOM_PESSOA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, C.COD_CARGA_EXTERNO, MS.DSC_QUEBRA, E.DTH_INICIO, P.COD_PESSOA, MS.COD_MAPA_SEPARACAO
                    ORDER BY PP.SEQUENCIA";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosClientesByExpedicao($idExpedicao)
    {
        $sql = "SELECT PP.QUANTIDADE_CONFERIDA, PP.SEQUENCIA, C.COD_CARGA_EXTERNO, PROD.COD_PRODUTO, PROD.DSC_GRADE, PROD.DSC_PRODUTO, LS.DSC_LINHA_SEPARACAO, E.DTH_INICIO, PP.DSC_PLACA_CARGA, P.NOM_PESSOA, NVL(PF.NUM_CPF, PJ.NUM_CNPJ) CPF_CNPJ, PE.DSC_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, S.COD_REFERENCIA_SIGLA
                    FROM MAPA_SEPARACAO_CONFERENCIA CONF
                    INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                    INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    LEFT JOIN (SELECT E.COD_EXPEDICAO, C.DSC_PLACA_CARGA, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) QUANTIDADE_CONFERIDA, P.COD_PESSOA
                                FROM EXPEDICAO E
                                INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                                INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                                WHERE E.COD_EXPEDICAO = $idExpedicao
                                GROUP BY E.COD_EXPEDICAO, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, C.DSC_PLACA_CARGA, P.COD_PESSOA
                                ) PP ON PP.COD_EXPEDICAO = E.COD_EXPEDICAO AND PP.COD_PRODUTO = CONF.COD_PRODUTO AND PP.DSC_GRADE = CONF.DSC_GRADE
                    INNER JOIN CARGA C ON PP.COD_CARGA = C.COD_CARGA
                    INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                    INNER JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = PP.COD_PESSOA
                    LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = P.COD_PESSOA
                    LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = P.COD_PESSOA
                    LEFT JOIN PESSOA_ENDERECO PE ON PE.COD_PESSOA = P.COD_PESSOA
                    LEFT JOIN SIGLA S ON S.COD_SIGLA = PE.COD_UF
                WHERE MS.COD_EXPEDICAO = $idExpedicao  AND CONF.COD_MAPA_SEPARACAO_EMBALADO IS NULL
                GROUP BY C.COD_CARGA, PP.SEQUENCIA, C.COD_CARGA_EXTERNO, PROD.COD_PRODUTO, PROD.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE_CONFERIDA, LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, E.DTH_INICIO, PP.DSC_PLACA_CARGA, P.NOM_PESSOA, PF.NUM_CPF, PJ.NUM_CNPJ, PE.DSC_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, S.COD_REFERENCIA_SIGLA
                ORDER BY P.NOM_PESSOA, PP.SEQUENCIA, PROD.COD_PRODUTO ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


}