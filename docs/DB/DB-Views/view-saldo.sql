/*
 DATA DE CRIAÇÃO: 04/06/2019 (Data de criação deste arquivo, a view já existia anteriormente)
 CRIADO POR: Tarcísio César

 ATUALIZAÇÕES:
 Data        Autor       Modificação
 04/06/19   (Tarcísio)   Correção para exibir produtos sem picking definido

 */

CREATE OR REPLACE FORCE VIEW V_SALDO_ESTOQUE AS
    SELECT E.COD_PRODUTO, E.DSC_GRADE,  E.COD_DEPOSITO_ENDERECO, E.DSC_DEPOSITO_ENDERECO, E.COD_LINHA_SEPARACAO, E.DSC_LINHA_SEPARACAO, SUM(E.QTD) as QTDE, E.COD_UNITIZADOR, E.DSC_UNITIZADOR, CONCAT(VOLUME,';CADASTRO') as VOLUME
    FROM (SELECT P.COD_PRODUTO, P.DSC_GRADE, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO,LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, NVL(E.QTD,0) AS QTD, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR,
                 CASE WHEN E.COD_PRODUTO_EMBALAGEM IS NOT NULL THEN 'PRODUTO UNITARIO'
                      WHEN E.COD_PRODUTO_VOLUME IS NOT NULL THEN PV.DSC_VOLUME
                     END as VOLUME
          FROM (SELECT DISTINCT P.COD_PRODUTO, P.DSC_GRADE,P.COD_LINHA_SEPARACAO,  NVL(PV.COD_DEPOSITO_ENDERECO, PE.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
                FROM PRODUTO P
                         LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE AND PV.COD_DEPOSITO_ENDERECO IS NOT NULL
                         LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE AND PE.COD_DEPOSITO_ENDERECO IS NOT NULL) P
                   LEFT JOIN ESTOQUE E ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE AND P.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                   LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
                   LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
                   INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = P.COD_DEPOSITO_ENDERECO
                   LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = E.COD_UNITIZADOR) E
    GROUP BY COD_PRODUTO, DSC_GRADE, COD_DEPOSITO_ENDERECO, DSC_DEPOSITO_ENDERECO, COD_LINHA_SEPARACAO, DSC_LINHA_SEPARACAO, COD_UNITIZADOR, DSC_UNITIZADOR, VOLUME
    UNION
    SELECT P.COD_PRODUTO, P.DSC_GRADE, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO,LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, SUM(NVL(E.QTD,0)) AS QTDE, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR as UNITIZADOR,
           CASE WHEN E.COD_PRODUTO_EMBALAGEM IS NOT NULL THEN 'PRODUTO UNITARIO'
                WHEN E.COD_PRODUTO_VOLUME IS NOT NULL THEN PV.DSC_VOLUME
               END as VOLUME
    FROM ESTOQUE E
             LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
             LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
             LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
             LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
             LEFT JOIN UNITIZADOR UN ON E.COD_UNITIZADOR = UN.COD_UNITIZADOR
             LEFT JOIN PARAMETRO PARAM ON PARAM.DSC_PARAMETRO = 'ID_CARACTERISTICA_PICKING'
    WHERE DE.COD_CARACTERISTICA_ENDERECO != PARAM.DSC_VALOR_PARAMETRO
    GROUP BY P.COD_PRODUTO, P.DSC_GRADE, LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR,PV.DSC_VOLUME, E.COD_PRODUTO_VOLUME, E.COD_PRODUTO_EMBALAGEM
    UNION
    SELECT E.COD_PRODUTO,
           E.DSC_GRADE,
           DE.COD_DEPOSITO_ENDERECO,
           DE.DSC_DEPOSITO_ENDERECO,
           LS.COD_LINHA_SEPARACAO,
           LS.DSC_LINHA_SEPARACAO,
           E.QTD,
           E.COD_UNITIZADOR,
           UN.DSC_UNITIZADOR,
           NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME
    FROM ESTOQUE E
             LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
             LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
             LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
             LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
             LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
             LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = E.COD_UNITIZADOR
    WHERE DE.COD_CARACTERISTICA_ENDERECO IN (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = 'ID_CARACTERISTICA_PICKING')
      AND (E.COD_DEPOSITO_ENDERECO <> NVL(NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO), 0));



CREATE OR REPLACE FORCE VIEW V_SALDO_ESTOQUE_COMPLETO AS
    SELECT E.COD_PRODUTO, E.DSC_GRADE,  E.COD_DEPOSITO_ENDERECO, E.DSC_DEPOSITO_ENDERECO, E.COD_LINHA_SEPARACAO, E.DSC_LINHA_SEPARACAO, SUM(E.QTD) as QTDE, E.COD_UNITIZADOR, E.DSC_UNITIZADOR, CONCAT(E.VOLUME,';CADASTRO') as VOLUME
    FROM (SELECT P.COD_PRODUTO, P.DSC_GRADE, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO,LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, NVL(E.QTD,0) AS QTD, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR,
                 CASE WHEN E.COD_PRODUTO_EMBALAGEM IS NOT NULL THEN 'PRODUTO UNITARIO'
                      WHEN E.COD_PRODUTO_VOLUME IS NOT NULL THEN PV.DSC_VOLUME
                     END as VOLUME
          FROM (SELECT DISTINCT P.COD_PRODUTO, P.DSC_GRADE,P.COD_LINHA_SEPARACAO,  NVL(PV.COD_DEPOSITO_ENDERECO, PE.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
                FROM PRODUTO P
                         LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE AND PV.COD_DEPOSITO_ENDERECO IS NOT NULL
                         LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE AND PE.COD_DEPOSITO_ENDERECO IS NOT NULL) P
                   LEFT JOIN ESTOQUE E ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE AND P.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                   LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
                   LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
                   INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = P.COD_DEPOSITO_ENDERECO
                   LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = E.COD_UNITIZADOR) E
    GROUP BY COD_PRODUTO, DSC_GRADE, COD_DEPOSITO_ENDERECO, DSC_DEPOSITO_ENDERECO, COD_LINHA_SEPARACAO, DSC_LINHA_SEPARACAO, COD_UNITIZADOR, DSC_UNITIZADOR, VOLUME
    UNION
    SELECT P.COD_PRODUTO, P.DSC_GRADE, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO,LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, SUM(NVL(E.QTD,0)) AS QTDE, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR as UNITIZADOR,
           CASE WHEN E.COD_PRODUTO_EMBALAGEM IS NOT NULL THEN 'PRODUTO UNITARIO'
                WHEN E.COD_PRODUTO_VOLUME IS NOT NULL THEN PV.DSC_VOLUME
               END as VOLUME
    FROM DEPOSITO_ENDERECO DE
             LEFT JOIN ESTOQUE E ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
             LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
             LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
             LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
             LEFT JOIN UNITIZADOR UN ON E.COD_UNITIZADOR = UN.COD_UNITIZADOR
             LEFT JOIN PARAMETRO PARAM ON PARAM.DSC_PARAMETRO = 'ID_CARACTERISTICA_PICKING'
    WHERE DE.COD_CARACTERISTICA_ENDERECO != PARAM.DSC_VALOR_PARAMETRO
    GROUP BY P.COD_PRODUTO, P.DSC_GRADE, LS.COD_LINHA_SEPARACAO, LS.DSC_LINHA_SEPARACAO, DE.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO, E.COD_UNITIZADOR, UN.DSC_UNITIZADOR, E.COD_PRODUTO_VOLUME, E.COD_PRODUTO_EMBALAGEM, PV.DSC_VOLUME
    UNION
    SELECT E.COD_PRODUTO,
           E.DSC_GRADE,
           DE.COD_DEPOSITO_ENDERECO,
           DE.DSC_DEPOSITO_ENDERECO,
           LS.COD_LINHA_SEPARACAO,
           LS.DSC_LINHA_SEPARACAO,
           E.QTD,
           E.COD_UNITIZADOR,
           UN.DSC_UNITIZADOR,
           NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME
    FROM ESTOQUE E
             LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
             LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
             LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
             LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
             LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
             LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = E.COD_UNITIZADOR
    WHERE DE.COD_CARACTERISTICA_ENDERECO IN (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = 'ID_CARACTERISTICA_PICKING')
      AND (E.COD_DEPOSITO_ENDERECO <> NVL(NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO), 0));