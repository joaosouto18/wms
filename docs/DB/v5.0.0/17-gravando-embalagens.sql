INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','17-gravando_embalagens.sql');

ALTER TABLE RECEBIMENTO_EMBALAGEM
  ADD (QTD_EMBALAGEM NUMBER(13,3));

ALTER TABLE ETIQUETA_SEPARACAO
  ADD (QTD_EMBALAGEM NUMBER(13,3));

MERGE INTO RECEBIMENTO_EMBALAGEM RE
USING (SELECT * FROM PRODUTO_EMBALAGEM) PE ON (PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM)
 WHEN MATCHED THEN UPDATE SET RE.QTD_EMBALAGEM = PE.QTD_EMBALAGEM;

MERGE INTO ETIQUETA_SEPARACAO ES
USING (SELECT * FROM PRODUTO_EMBALAGEM) PE ON (PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM)
 WHEN MATCHED THEN UPDATE SET ES.QTD_EMBALAGEM = PE.QTD_EMBALAGEM;

UPDATE ETIQUETA_SEPARACAO SET QTD_EMBALAGEM = 1 WHERE QTD_EMBALAGEM IS NULL;

CREATE OR REPLACE VIEW V_QTD_RECEBIMENTO ( QTD, COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, COD_OS, COD_NORMA_PALETIZACAO, NUM_PESO)
AS SELECT MIN(QTD.QTD) as QTD,
    QTD.COD_RECEBIMENTO,
    QTD.COD_PRODUTO,
    QTD.DSC_GRADE,
    QTD.COD_OS,
    NP.COD_NORMA_PALETIZACAO,
    SUM(QTD.NUM_PESO)
  FROM (SELECT SUM(NVL(RV.QTD_CONFERIDA,0)) AS QTD,
          RV.COD_RECEBIMENTO,
          RV.COD_PRODUTO_VOLUME,
          PV.COD_PRODUTO,
          PV.DSC_GRADE,
          OS.COD_OS,
               SUM(RV.NUM_PESO) AS NUM_PESO
        FROM RECEBIMENTO_VOLUME RV
          INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                        COD_OS,
                        COD_PRODUTO_VOLUME,
                        COD_RECEBIMENTO,
                        RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO_VOLUME ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                      FROM (SELECT CASE WHEN DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
                                   ELSE DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
                              OS.COD_OS,
                              OS.COD_RECEBIMENTO,
                              RV.COD_PRODUTO_VOLUME
                            FROM RECEBIMENTO_VOLUME RV
                              LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RV.COD_OS)) OS
            ON OS.COD_OS = RV.COD_OS
               AND OS.RANK <= 1
               AND OS.COD_RECEBIMENTO = RV.COD_RECEBIMENTO
               AND OS.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
        GROUP BY RV.COD_RECEBIMENTO, RV.COD_PRODUTO_VOLUME, PV.COD_PRODUTO, PV.DSC_GRADE, OS.COD_OS, RV.NUM_PESO) QTD
    LEFT JOIN (SELECT MIN(RV.COD_NORMA_PALETIZACAO) as COD_NORMA_PALETIZACAO,
                 RV.COD_RECEBIMENTO,
                 PV.COD_PRODUTO,
                 PV.DSC_GRADE,
                 OS.COD_OS,
                      SUM(RV.NUM_PESO) AS NUM_PESO,
                      MIN(NP.NUM_NORMA) as NORMA
               FROM RECEBIMENTO_VOLUME RV
                 INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                               COD_OS,
                               COD_PRODUTO_VOLUME,
                               COD_RECEBIMENTO,
                               RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO_VOLUME ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                             FROM (SELECT CASE WHEN DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
                                          ELSE DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
                                     OS.COD_OS,
                                     OS.COD_RECEBIMENTO,
                                     RV.COD_PRODUTO_VOLUME
                                   FROM RECEBIMENTO_VOLUME RV
                                     LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RV.COD_OS)) OS
                   ON OS.COD_OS = RV.COD_OS
                      AND OS.RANK <= 1
                      AND OS.COD_RECEBIMENTO = RV.COD_RECEBIMENTO
                      AND OS.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                 LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                 LEFT JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = RV.COD_NORMA_PALETIZACAO
               GROUP BY RV.COD_RECEBIMENTO,  PV.COD_PRODUTO, PV.DSC_GRADE, OS.COD_OS, RV.NUM_PESO) NP
      ON NP.COD_RECEBIMENTO = QTD.COD_RECEBIMENTO
         AND NP.COD_PRODUTO = QTD.COD_PRODUTO
         AND NP.DSC_GRADE = QTD.DSC_GRADE
         AND NP.COD_OS = QTD.COD_OS
  GROUP BY QTD.COD_PRODUTO, QTD.COD_RECEBIMENTO, QTD.DSC_GRADE, QTD.COD_OS,QTD.NUM_PESO,NP.COD_NORMA_PALETIZACAO
  UNION
  SELECT SUM(RE.QTD_CONFERIDA * RE.QTD_EMBALAGEM) AS QTD,
         RE.COD_RECEBIMENTO,
         PE.COD_PRODUTO,
         PE.DSC_GRADE,
         OS.COD_OS,
         RE.COD_NORMA_PALETIZACAO,
         SUM(RE.NUM_PESO) AS NUM_PESO
  FROM RECEBIMENTO_EMBALAGEM RE
  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
    INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                  COD_OS,
                  COD_PRODUTO,
                  COD_RECEBIMENTO,
                  DSC_GRADE,
                  RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                FROM (SELECT
                        DISTINCT CASE WHEN MAX(DTH_FINAL_ATIVIDADE) IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy')
                             ELSE MAX(DTH_FINAL_ATIVIDADE) END AS DTH_FINAL_ATIVIDADE,
                        MAX(OS.COD_OS) COD_OS,
                        OS.COD_RECEBIMENTO,
                        P.COD_PRODUTO,
                        P.DSC_GRADE
                      FROM RECEBIMENTO_EMBALAGEM RE
                        INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                        INNER JOIN PRODUTO P ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RE.COD_OS
                        GROUP BY OS.COD_RECEBIMENTO,
                        P.COD_PRODUTO,
                        P.DSC_GRADE)) OS
      ON OS.COD_OS = RE.COD_OS
         AND OS.RANK <= 1
         AND OS.COD_RECEBIMENTO = RE.COD_RECEBIMENTO
         AND OS.COD_PRODUTO = PE.COD_PRODUTO
         AND OS.DSC_GRADE = PE.DSC_GRADE
    GROUP BY RE.COD_RECEBIMENTO,
    PE.COD_PRODUTO,
    PE.DSC_GRADE,
    OS.COD_OS,
    RE.COD_NORMA_PALETIZACAO;

ALTER TABLE PRODUTO_EMBALAGEM MODIFY(CAPACIDADE_PICKING NUMBER(17,4));