<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Math;

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

    public function getConferidosByExpedicao($idExpedicao,$idLinhaSeparacao)
    {
        $andWhere = '';
        if (isset($idLinhaSeparacao) && !empty($idLinhaSeparacao) && $idLinhaSeparacao != 'null') {
            $andWhere = " AND LS.COD_LINHA_SEPARACAO = $idLinhaSeparacao ";
        }
        $sql = "SELECT SUM(P.QTD_SEPARAR - NVL(EMB.QTD_EMBALADO,0)) as QUANTIDADE_CONFERIDA,
                   P.SEQUENCIA,
                   E.DTH_INICIO,
                   PROD.COD_PRODUTO,
                   PROD.DSC_GRADE,
                   PROD.DSC_PRODUTO,
                   LS.DSC_LINHA_SEPARACAO,
                   E.DSC_PLACA_EXPEDICAO AS DSC_PLACA_CARGA
              FROM (SELECT C.COD_EXPEDICAO, C.COD_CARGA, C.COD_CARGA_EXTERNO, P.SEQUENCIA,  P.COD_PESSOA, PP.COD_PRODUTO, PP.DSC_GRADE, SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) as QTD_SEPARAR
                      FROM PEDIDO P
                      LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                      LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                      LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO
                      LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = PROD.COD_LINHA_SEPARACAO
                     WHERE  C.COD_EXPEDICAO = $idExpedicao
                     GROUP BY C.COD_EXPEDICAO, C.COD_CARGA, C.COD_CARGA_EXTERNO, P.SEQUENCIA, P.COD_PESSOA, PP.COD_PRODUTO, PP.DSC_GRADE) P
              LEFT JOIN (SELECT COD_EXPEDICAO, P.COD_CARGA, P.COD_PESSOA, MSP.COD_PRODUTO, MSP.DSC_GRADE, SUM((QTD_SEPARAR * QTD_EMBALAGEM) - QTD_CORTADO) as QTD_EMBALADO
                           FROM MAPA_SEPARACAO_PRODUTO MSP
                           LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                           LEFT JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MSQ.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                           LEFT JOIN PEDIDO P ON P.cOD_PEDIDO = PP.COD_PEDIDO
                           LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                          WHERE MS.COD_EXPEDICAO = $idExpedicao
                            AND MSQ.IND_TIPO_QUEBRA = 'T'
                          GROUP BY COD_EXPEDICAO, P.COD_CARGA, P.COD_PESSOA, MSP.COD_PRODUTO, MSP.DSC_GRADE) EMB
                    ON EMB.COD_PESSOA = P.COD_PESSOA
                   AND EMB.COD_PRODUTO = P.COD_PRODUTO
                   AND EMB.DSC_GRADE = P.DSC_GRADE
                   AND EMB.COD_CARGA = P.COD_CARGA
              LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
              LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = P.COD_EXPEDICAO
              LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = P.COD_PRODUTO
                                    AND PROD.DSC_GRADE = P.DSC_GRADE
              LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = PROD.COD_LINHA_SEPARACAO                        
              WHERE P.QTD_SEPARAR - NVL(EMB.QTD_EMBALADO,0) > 0 
              $andWhere
              GROUP BY P.SEQUENCIA,
                   E.DTH_INICIO,
                   PROD.COD_PRODUTO,
                   PROD.DSC_GRADE,
                   PROD.DSC_PRODUTO,
                   LS.DSC_LINHA_SEPARACAO,
                   E.DSC_PLACA_EXPEDICAO
             ORDER BY P.SEQUENCIA, PROD.COD_PRODUTO";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEmbaladosConferidosByExpedicao($idExpedicao,$idLinhaSeparacao)
    {
        $andWhere = '';
        if (isset($idLinhaSeparacao) && !empty($idLinhaSeparacao) && $idLinhaSeparacao != 'null') {
            $andWhere = " AND LS.COD_LINHA_SEPARACAO = $idLinhaSeparacao ";
        }
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
                    WHERE MS.COD_EXPEDICAO = $idExpedicao $andWhere
                    GROUP BY PP.SEQUENCIA, P.NOM_PESSOA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, C.COD_CARGA_EXTERNO, MS.DSC_QUEBRA, E.DTH_INICIO, P.COD_PESSOA, MS.COD_MAPA_SEPARACAO
                    ORDER BY PP.SEQUENCIA";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosClientesByExpedicao($idExpedicao,$idLinhaSeparacao)
    {
        $andWhere = '';
        if (isset($idLinhaSeparacao) && !empty($idLinhaSeparacao) && $idLinhaSeparacao != 'null') {
            $andWhere = " AND LS.COD_LINHA_SEPARACAO = $idLinhaSeparacao ";
        }
        $sql = "SELECT 
                  PP.QUANTIDADE_CONFERIDA - SUM(NVL(QTD_EMBALADOS.QTD_EMBALADO,0)) QUANTIDADE_CONFERIDA, 
                  PP.SEQUENCIA, 
                  C.COD_CARGA_EXTERNO,
                  P.NOM_PESSOA,
                  P.COD_PESSOA,
                  E.DTH_INICIO,
                  PROD.COD_PRODUTO, 
                  PROD.DSC_GRADE, 
                  PROD.DSC_PRODUTO, 
                  LS.DSC_LINHA_SEPARACAO,
                  PP.DSC_PLACA_CARGA,  
                  NVL(PF.NUM_CPF, PJ.NUM_CNPJ) CPF_CNPJ, 
                  PE.DSC_ENDERECO, 
                  PE.NOM_BAIRRO,
                  PE.NOM_LOCALIDADE, 
                  S.COD_REFERENCIA_SIGLA, 
                  PP.COD_PEDIDO,  
                  NULL AS COD_MAPA_SEPARACAO_EMB_CLIENTE, 
                  NULL AS DSC_QUEBRA, 
                  NULL AS COD_MAPA_SEPARACAO  
              FROM MAPA_SEPARACAO_CONFERENCIA CONF
              INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
              INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
              INNER JOIN (
                            SELECT E.COD_EXPEDICAO, PP.COD_PEDIDO_PRODUTO, C.DSC_PLACA_CARGA, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) QUANTIDADE_CONFERIDA, P.COD_PESSOA, P.COD_PEDIDO
                              FROM EXPEDICAO E 
                              INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO 
                              INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA 
                              INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                             WHERE E.COD_EXPEDICAO = $idExpedicao 
                            GROUP BY E.COD_EXPEDICAO, PP.COD_PEDIDO_PRODUTO, C.COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, P.SEQUENCIA, C.DSC_PLACA_CARGA, P.COD_PESSOA, P.COD_PEDIDO
                         ) PP ON PP.COD_EXPEDICAO = E.COD_EXPEDICAO AND PP.COD_PRODUTO = CONF.COD_PRODUTO AND PP.DSC_GRADE = CONF.DSC_GRADE
              INNER JOIN MAPA_SEPARACAO_PEDIDO MSPED ON MSPED.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO AND MSPED.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
              INNER JOIN CARGA C ON PP.COD_CARGA = C.COD_CARGA 
              INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
              LEFT JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO 
              INNER JOIN PESSOA P ON P.COD_PESSOA = PP.COD_PESSOA
              LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = P.COD_PESSOA 
              LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = P.COD_PESSOA 
              LEFT JOIN PEDIDO_ENDERECO PE ON PP.COD_PEDIDO = PE.COD_PEDIDO
              LEFT JOIN SIGLA S ON S.COD_SIGLA = PE.COD_UF
              
              LEFT JOIN (
                          SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) QTD_EMBALADO, COD_PESSOA, MSC.COD_MAPA_SEPARACAO
                            FROM MAPA_SEPARACAO_CONFERENCIA MSC
                            INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                           WHERE COD_MAPA_SEPARACAO_EMBALADO IS NOT NULL AND MS.COD_EXPEDICAO = 9123
                          GROUP BY COD_PRODUTO, DSC_GRADE, COD_PESSOA, MSC.COD_MAPA_SEPARACAO
                        ) QTD_EMBALADOS ON P.COD_PESSOA = QTD_EMBALADOS.COD_PESSOA AND CONF.COD_PRODUTO = QTD_EMBALADOS.COD_PRODUTO AND CONF.DSC_GRADE = QTD_EMBALADOS.DSC_GRADE
             
              
             WHERE MS.COD_EXPEDICAO = $idExpedicao AND CONF.COD_MAPA_SEPARACAO_EMBALADO IS NULL $andWhere
            GROUP BY QTD_EMBALADOS.QTD_EMBALADO, P.COD_PESSOA, C.COD_CARGA, PP.SEQUENCIA, C.COD_CARGA_EXTERNO, PROD.COD_PRODUTO, PROD.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE_CONFERIDA, LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, E.DTH_INICIO, PP.DSC_PLACA_CARGA, P.NOM_PESSOA, PF.NUM_CPF, PJ.NUM_CNPJ, PE.DSC_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, S.COD_REFERENCIA_SIGLA, PP.COD_PEDIDO 
        UNION
                SELECT  
                  COUNT(DISTINCT MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE) QUANTIDADE_CONFERIDA, 
                  PP.SEQUENCIA,
                  C.COD_CARGA_EXTERNO, 
                  P.NOM_PESSOA,
                  P.COD_PESSOA,
                  E.DTH_INICIO,
                  NULL AS COD_PRODUTO, 
                  NULL AS DSC_GRADE, 
                  NULL AS DSC_PRODUTO, 
                  NULL AS DSC_LINHA_SEPARACAO,
                  NULL AS DSC_PLACA_CARGA,
                  NULL AS CPF_CNPJ, 
                  NULL AS DSC_ENDERECO, 
                  NULL AS NOM_BAIRRO,
                  NULL AS NOM_LOCALIDADE, 
                  NULL AS COD_REFERENCIA_SIGLA, 
                  NULL AS COD_PEDIDO,          
                  MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, 
                  MS.DSC_QUEBRA,            
                  MS.COD_MAPA_SEPARACAO
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
              LEFT JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
              INNER JOIN PESSOA P ON P.COD_PESSOA = CONF.COD_PESSOA AND P.COD_PESSOA = MSE.COD_PESSOA
             WHERE MS.COD_EXPEDICAO = $idExpedicao
            GROUP BY PP.SEQUENCIA, P.NOM_PESSOA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, C.COD_CARGA_EXTERNO, MS.DSC_QUEBRA, E.DTH_INICIO, P.COD_PESSOA, MS.COD_MAPA_SEPARACAO
            ORDER BY SEQUENCIA, COD_PESSOA, CPF_CNPJ, COD_PRODUTO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQuantidadesConferidasToForcarConferencia($idExpedicao)
    {
        $sql = "SELECT MS.COD_MAPA_SEPARACAO, MSPROD.COD_PRODUTO, MSPROD.DSC_GRADE, MSPROD.COD_PRODUTO_VOLUME, MSPROD.COD_PRODUTO_EMBALAGEM, MSPROD.QTD_EMBALAGEM,
                    ((NVL(MSPROD.QTD_SEPARAR * MSPROD.QTD_EMBALAGEM,0) - (NVL(MSC.QTD_CONFERIDA,0) * NVL(MSC.QTD_EMBALAGEM,0)) - NVL(MSPROD.QTD_CORTADO,0))) QTD_CONFERIR
                    FROM EXPEDICAO E
                    INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                    INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    INNER JOIN MAPA_SEPARACAO_PRODUTO MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND PP.COD_PRODUTO = MSPROD.COD_PRODUTO AND PP.DSC_GRADE = MSPROD.DSC_GRADE AND MSPROD.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                    LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSPROD.COD_PRODUTO AND MSC.DSC_GRADE = MSPROD.DSC_GRADE
                    WHERE E.COD_EXPEDICAO = $idExpedicao
                    AND ((NVL(MSPROD.QTD_SEPARAR * MSPROD.QTD_EMBALAGEM,0) - (NVL(MSC.QTD_CONFERIDA,0) * NVL(MSC.QTD_EMBALAGEM,0)) - NVL(MSPROD.QTD_CORTADO,0)) / MSPROD.QTD_EMBALAGEM) > 0";

        $sql = "
        SELECT MSP.COD_PRODUTO, NVL(MSC.QTD_CONF,0) as QTD_CONFERIDA, (MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) - MSP.QTD_CORTADO as QTD_SEPARAR,
                       (MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) - MSP.QTD_CORTADO - NVL(MSC.QTD_CONF,0) as QTD_CONFERIR,
                       PE.COD_PRODUTO_EMBALAGEM,
                       MSP.DSC_GRADE,
                       MSP.COD_MAPA_SEPARACAO,
                       MSP.COD_PRODUTO_VOLUME,
                       PE.QTD_EMBALAGEM
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM
                  LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO  = MSP.COD_MAPA_SEPARACAO
                  LEFT JOIN (SELECT MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, SUM(NVL(QTD_EMBALAGEM,1) * NVL(QTD_CONFERIDA,1)) as QTD_CONF
                               FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              GROUP BY COD_PRODUTO, DSC_GRADE, COD_MAPA_SEPARACAO) MSC
                              ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                             AND MSC.COD_PRODUTO = MSP.COD_PRODUTO
                             AND MSC.DSC_GRADE = MSP.DSC_GRADE
                  WHERE MS.COD_EXPEDICAO = $idExpedicao
                    AND (MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) - MSP.QTD_CORTADO - NVL(MSC.QTD_CONF,0) > 0
                  ORDER BY MSP.COD_MAPA_SEPARACAO
        ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosClientesInConferencia($idExpedicao)
    {
        $sql = "
            SELECT COUNT(MS.COD_MAPA_SEPARACAO) 
            FROM MAPA_SEPARACAO MS
            INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
            INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
            AND MSP.COD_PRODUTO = MSC.COD_PRODUTO 
            AND MSP.DSC_GRADE = MSC.DSC_GRADE
            WHERE MS.COD_EXPEDICAO = $idExpedicao
            GROUP BY MSP.QTD_CORTADO
            HAVING ((SUM(MSP.QTD_SEPARAR  * MSP.QTD_EMBALAGEM) - MSP.QTD_CORTADO) - SUM(MSC.QTD_CONFERIDA *  MSC.QTD_EMBALAGEM)) > 0
        ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function removeMapaSeparacaConferencia($dados)
    {
        $codMapaSeparacao = $dados['COD_MAPA_SEPARACAO'];
        $codProduto = $dados['COD_PRODUTO'];
        $grade = $dados['DSC_GRADE'];
        $mapaSeparacaoEntity = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$codMapaSeparacao);
        $mapaSeparacaoConferenciaEntities = $this->findBy(array('codMapaSeparacao' => $mapaSeparacaoEntity->getId(), 'codProduto' => $codProduto, 'dscGrade' => $grade));

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $expedicaoAndamentoRepository */
        $expedicaoAndamentoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
        if (!isset($mapaSeparacaoConferenciaEntities) || empty($mapaSeparacaoConferenciaEntities))
            throw new \Exception("Não existe conferências para o mapa de separação $codMapaSeparacao e produto $codProduto / $grade");

        $quantidade = 0;
        foreach ($mapaSeparacaoConferenciaEntities as $mapaSeparacaoConferenciaEntity) {
            $quantidade = Math::adicionar($quantidade, Math::multiplicar($mapaSeparacaoConferenciaEntity->getQtdConferida(), $mapaSeparacaoConferenciaEntity->getQtdEmbalagem()));
            $this->getEntityManager()->remove($mapaSeparacaoConferenciaEntity);
        }
        $expedicaoAndamentoRepository->save("Conferência do produto $codProduto grade $grade com quantidade de $quantidade no mapa de separação $codMapaSeparacao foi reiniciada", $mapaSeparacaoEntity->getCodExpedicao());
        $this->getEntityManager()->flush();
        return array(
            'quantidade' => $quantidade,
            'expedicao' => $mapaSeparacaoEntity->getCodExpedicao()
        );
    }

}