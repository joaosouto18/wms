INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', 'xx-view-recebimento.sql');

CREATE OR REPLACE FORCE VIEW "V_QTD_RECEBIMENTO" ("QTD", "COD_RECEBIMENTO", "COD_PRODUTO", "DSC_GRADE", "COD_OS", "NUM_PESO", "DSC_LOTE") AS
SELECT MIN(QTD.QTD) as QTD,
      QTD.COD_RECEBIMENTO,
      QTD.COD_PRODUTO,
      QTD.DSC_GRADE,
      QTD.COD_OS,
      QTD.NUM_PESO,
      QTD.DSC_LOTE
    FROM (SELECT
            SUM(NVL(RV.QTD_CONFERIDA,0)) AS QTD,
            RV.COD_RECEBIMENTO,
            RV.COD_PRODUTO_VOLUME,
            PV.COD_PRODUTO,
            PV.DSC_GRADE,
            OS.COD_OS,
            SUM(RV.NUM_PESO) AS NUM_PESO,
            RV.DSC_LOTE
          FROM RECEBIMENTO_VOLUME RV
            INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
            INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                          COD_OS,
                          COD_PRODUTO_VOLUME,
                          COD_RECEBIMENTO,
                          RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO_VOLUME ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                        FROM (SELECT NVL( OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999','dd/mm/yyyy')) AS DTH_FINAL_ATIVIDADE,
                                OS.COD_OS,
                                OS.COD_RECEBIMENTO,
                                RV.COD_PRODUTO_VOLUME
                              FROM RECEBIMENTO_VOLUME RV
                                LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RV.COD_OS)) OS
              ON OS.COD_OS = RV.COD_OS
                 AND OS.RANK <= 1
                 AND OS.COD_RECEBIMENTO = RV.COD_RECEBIMENTO
                 AND OS.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
          GROUP BY RV.COD_RECEBIMENTO, RV.COD_PRODUTO_VOLUME, PV.COD_PRODUTO, PV.DSC_GRADE, OS.COD_OS, RV.NUM_PESO, RV.DSC_LOTE) QTD
    GROUP BY QTD.COD_PRODUTO, QTD.COD_RECEBIMENTO, QTD.DSC_GRADE, QTD.COD_OS, QTD.NUM_PESO, QTD.DSC_LOTE
    UNION
    SELECT SUM(RE.QTD_CONFERIDA * RE.QTD_EMBALAGEM) AS QTD,
      RE.COD_RECEBIMENTO,
      PE.COD_PRODUTO,
      PE.DSC_GRADE,
      OS.COD_OS,
      SUM(RE.NUM_PESO) AS NUM_PESO,
      RE.DSC_LOTE
    FROM RECEBIMENTO_EMBALAGEM RE
      INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
      INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                    COD_OS,
                    COD_RECEBIMENTO,
                    COD_PRODUTO,
                    DSC_GRADE,
                    RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                  FROM (SELECT DISTINCT
                          NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')) AS DTH_FINAL_ATIVIDADE,
                          MAX(OS.COD_OS) COD_OS,
                          OS.COD_RECEBIMENTO,
                          PE.COD_PRODUTO,
                          PE.DSC_GRADE
                        FROM RECEBIMENTO_EMBALAGEM RE
                        INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                        LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RE.COD_OS
                        GROUP BY OS.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, RE.DSC_LOTE, NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')))) OS
        ON OS.COD_OS = RE.COD_OS
           AND OS.RANK <= 1
           AND OS.COD_RECEBIMENTO = RE.COD_RECEBIMENTO
           AND OS.COD_PRODUTO = PE.COD_PRODUTO
           AND OS.DSC_GRADE = PE.DSC_GRADE
    GROUP BY RE.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, OS.COD_OS, RE.DSC_LOTE;

----------------------------------------------------------------------------------------------------------

--DETALHADA
CREATE OR REPLACE FORCE VIEW "V_QTD_RECEBIMENTO_DETALHADA" ("QTD", "COD_RECEBIMENTO", "COD_PRODUTO", "DSC_GRADE", "COD_OS", "COD_NORMA_PALETIZACAO", "NUM_PESO", "DSC_LOTE") AS
SELECT MIN(QTD.QTD) as QTD,
      QTD.COD_RECEBIMENTO,
      QTD.COD_PRODUTO,
      QTD.DSC_GRADE,
      QTD.COD_OS,
      QTD.COD_NORMA_PALETIZACAO,
      QTD.NUM_PESO,
      QTD.DSC_LOTE
    FROM (SELECT
            SUM(NVL(RV.QTD_CONFERIDA,0)) AS QTD,
            RV.COD_NORMA_PALETIZACAO,
            RV.COD_RECEBIMENTO,
            RV.COD_PRODUTO_VOLUME,
            PV.COD_PRODUTO,
            PV.DSC_GRADE,
            OS.COD_OS,
            SUM(RV.NUM_PESO) AS NUM_PESO,
            RV.DSC_LOTE
          FROM RECEBIMENTO_VOLUME RV
            INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
            INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                          COD_OS,
                          COD_PRODUTO_VOLUME,
                          COD_RECEBIMENTO,
                          RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO_VOLUME ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                        FROM (SELECT NVL( OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999','dd/mm/yyyy')) AS DTH_FINAL_ATIVIDADE,
                                OS.COD_OS,
                                OS.COD_RECEBIMENTO,
                                RV.COD_PRODUTO_VOLUME
                              FROM RECEBIMENTO_VOLUME RV
                                LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RV.COD_OS)) OS
              ON OS.COD_OS = RV.COD_OS
                 AND OS.RANK <= 1
                 AND OS.COD_RECEBIMENTO = RV.COD_RECEBIMENTO
                 AND OS.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
          GROUP BY RV.COD_RECEBIMENTO, RV.COD_PRODUTO_VOLUME, PV.COD_PRODUTO, PV.DSC_GRADE, RV.COD_NORMA_PALETIZACAO, OS.COD_OS, RV.DSC_LOTE) QTD
    GROUP BY QTD.COD_PRODUTO, QTD.COD_RECEBIMENTO, QTD.DSC_GRADE, QTD.COD_OS, QTD.NUM_PESO, QTD.COD_NORMA_PALETIZACAO, QTD.DSC_LOTE
    UNION
    SELECT SUM(RE.QTD_CONFERIDA * RE.QTD_EMBALAGEM) AS QTD,
      RE.COD_RECEBIMENTO,
      PE.COD_PRODUTO,
      PE.DSC_GRADE,
      OS.COD_OS,
      RE.COD_NORMA_PALETIZACAO,
      SUM(RE.NUM_PESO) AS NUM_PESO,
      RE.DSC_LOTE
    FROM RECEBIMENTO_EMBALAGEM RE
      INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
      INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                    COD_OS,
                    COD_RECEBIMENTO,
                    COD_PRODUTO,
                    DSC_GRADE,
                    RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                  FROM (SELECT DISTINCT
                          NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')) AS DTH_FINAL_ATIVIDADE,
                          MAX(OS.COD_OS) COD_OS,
                          OS.COD_RECEBIMENTO,
                          PE.COD_PRODUTO,
                          PE.DSC_GRADE
                        FROM RECEBIMENTO_EMBALAGEM RE
                        INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                        LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RE.COD_OS
                        GROUP BY OS.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')))) OS
        ON OS.COD_OS = RE.COD_OS
           AND OS.RANK <= 1
           AND OS.COD_RECEBIMENTO = RE.COD_RECEBIMENTO
           AND OS.COD_PRODUTO = PE.COD_PRODUTO
           AND OS.DSC_GRADE = PE.DSC_GRADE
    GROUP BY RE.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, OS.COD_OS,  RE.COD_NORMA_PALETIZACAO, RE.DSC_LOTE;