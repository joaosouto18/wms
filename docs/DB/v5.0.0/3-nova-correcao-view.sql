INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','3 nova-correcao-view.sql');

CREATE OR REPLACE FORCE VIEW V_QTD_RECEBIMENTO ("QTD", "COD_RECEBIMENTO", "COD_PRODUTO", "DSC_GRADE", "COD_OS", "COD_NORMA_PALETIZACAO", "NUM_PESO") AS
SELECT NVL(RV.QTD, RE.QTD) as QTD,
       R.COD_RECEBIMENTO,
       NVL(RE.COD_PRODUTO,RV.COD_PRODUTO) as COD_PRODUTO,
       NVL(RE.DSC_GRADE,RV.DSC_GRADE) as DSC_GRADE,
       NVL(RE.COD_OS,RV.COD_OS) as COD_OS,
       NVL(RV.COD_NORMA_PALETIZACAO,RV.COD_NORMA_PALETIZACAO) as COD_NORMA_PALETIZACAO,
       NVL(RE.NUM_PESO, RV.NUM_PESO) as NUM_PESO
  FROM (SELECT DISTINCT R.COD_RECEBIMENTO, NFI.COD_PRODUTO, NFI.DSC_GRADE
               FROM RECEBIMENTO R
               LEFT JOIN NOTA_FISCAL NF ON NF.COD_RECEBIMENTO = R.COD_RECEBIMENTO
               LEFT JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL) R
  LEFT JOIN (SELECT MIN(QTD.QTD) as QTD,
                    QTD.COD_RECEBIMENTO,
                    QTD.COD_PRODUTO,
                    QTD.DSC_GRADE,
                    QTD.COD_OS,
                    NP.COD_NORMA_PALETIZACAO,
                    SUM(QTD.NUM_PESO) as NUM_PESO
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
                  GROUP BY QTD.COD_PRODUTO, QTD.COD_RECEBIMENTO, QTD.DSC_GRADE, QTD.COD_OS,QTD.NUM_PESO,NP.COD_NORMA_PALETIZACAO) RV
    ON RV.COD_RECEBIMENTO = R.COD_RECEBIMENTO
   AND RV.COD_PRODUTO = R.COD_PRODUTO
   AND RV.DSC_GRADE = R.DSC_GRADE
  LEFT JOIN (SELECT SUM(RE.QTD_CONFERIDA * PE.QTD_EMBALAGEM) AS QTD,
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
                                 COD_RECEBIMENTO,
                                 COD_PRODUTO,
                                 DSC_GRADE,
                                 RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                            FROM (SELECT DISTINCT CASE WHEN MAX(DTH_FINAL_ATIVIDADE) IS NULL THEN TO_DATE('31/12/9999','dd/mm/yyyy') ELSE MAX(DTH_FINAL_ATIVIDADE) END AS DTH_FINAL_ATIVIDADE,
                                         MAX(OS.COD_OS) COD_OS,
                                         OS.COD_RECEBIMENTO,
                                         PE.COD_PRODUTO,
                                         PE.DSC_GRADE
                                    FROM RECEBIMENTO_EMBALAGEM RE
                                   INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                                    LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RE.COD_OS
                                   GROUP BY OS.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE)) OS
                      ON OS.COD_OS = RE.COD_OS
                     AND OS.RANK <= 1
                     AND OS.COD_RECEBIMENTO = RE.COD_RECEBIMENTO
                     AND OS.COD_PRODUTO = PE.COD_PRODUTO
                     AND OS.DSC_GRADE = PE.DSC_GRADE
                   GROUP BY RE.COD_RECEBIMENTO,
                            PE.COD_PRODUTO,
                            PE.DSC_GRADE,
                            OS.COD_OS,
                            RE.COD_NORMA_PALETIZACAO) RE
         ON RE.COD_RECEBIMENTO = R.COD_RECEBIMENTO
        AND RE.COD_PRODUTO = R.COD_PRODUTO
        AND RE.DSC_GRADE = R.DSC_GRADE;