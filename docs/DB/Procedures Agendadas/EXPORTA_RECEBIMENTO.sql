create or replace PROCEDURE "EXPORTA_RECEBIMENTO" AS
BEGIN   

execute immediate '
CREATE TABLE EXPORTACAO_RECEBIMENTO AS
SELECT REC.COD_RECEBIMENTO as "COD_RECEBIMENTO",
                       TO_CHAR(REC.DTH_INICIO_RECEB,''DD/MM/YYYY HH24:MI:SS'') as "DTH_INICIO",
                       TO_CHAR(REC.DTH_FINAL_RECEB,''DD/MM/YYYY HH24:MI:SS'') as "DTH_FINALIZACAO",
                       SREC.DSC_SIGLA as "STATUS_RECEBIMENTO",
                       NF.NUM_NOTA_FISCAL as "NF",
                       NF.COD_SERIE_NOTA_FISCAL as "SERIE",
                       TO_CHAR(NF.DAT_EMISSAO,''DD/MM/YYYY HH24:MI:SS'') as "DTH_EMISSAO",
                       TO_CHAR(NF.DTH_ENTRADA,''DD/MM/YYYY HH24:MI:SS'') as "DTH_ENTRADA",
                       NFI.COD_PRODUTO as "COD_PRODUTO",
                       NFI.DSC_GRADE as "GRADE",
                       PROD.DSC_PRODUTO as "PRODUTO",
                       NFI.QTD_ITEM as "QTD_NF",
                       OSREC.COD_OS as "OS",
                       OSREC.DSC_OBSERVACAO as "OBSERVACAO_OS",
                       TO_CHAR(OSREC.DTH_INICIO_ATIVIDADE,''DD/MM/YYYY HH24:MI:SS'') as "DTH_INICIO_CONFERENCIA",
                       TO_CHAR(OSREC.DTH_FINAL_ATIVIDADE,''DD/MM/YYYY HH24:MI:SS'') as "DTH_FINAL_CONFERENCIA",
                       CONF.NOM_PESSOA as "CONFERENTE",
                       RC.QTD_CONFERIDA as "QTD_CONFERIDA",
                       RC.QTD_AVARIA as "AVARIA",
                       RC.QTD_DIVERGENCIA as "DIVERGENCIA",
                       MOT.DSC_MOTIVO_DIVER_RECEB as "MOTIVO_DIVERGENCIA",
                       VPES.PESO as "PESO_TOTAL",
                       VPES.CUBAGEM as "CUBAGEM",
                       UMA.UMA as "UMA",
                       UMA.QTD as "QTD_NA_UMA",
                       NP.NUM_LASTRO as "LASTRO",
                       NP.NUM_CAMADAS as "CAMADAS",
                       U.DSC_UNITIZADOR as "UNITIZADOR",
                       SUMA.DSC_SIGLA as "STATUS_UMA",
                       DE.DSC_DEPOSITO_ENDERECO as "ENDERECO_ARMAZENAGEM",
                       OSUMA.COD_OS as "OS_UMA",
                       TO_CHAR(OSUMA.DTH_INICIO_ATIVIDADE,''DD/MM/YYYY HH24:MI:SS'') as "DTH_ARMAZENAGEM",
                       OPEMP.NOM_PESSOA as "OPERADOR_EMPILHADEIRA",
					   PF.NUM_CPF AS CPF_CONFERENTE,
                       PF_EMP.NUM_CPF AS CPF_OPERADOR_EMPILHADEIRA					   
                  FROM RECEBIMENTO                   REC
                  LEFT JOIN SIGLA                    SREC  ON REC.COD_STATUS = SREC.COD_SIGLA
                  LEFT JOIN NOTA_FISCAL              NF    ON REC.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
                  LEFT JOIN NOTA_FISCAL_ITEM         NFI   ON NF.COD_NOTA_FISCAL  = NFI.COD_NOTA_FISCAL
                  LEFT JOIN PRODUTO                  PROD  ON NFI.COD_PRODUTO = PROD.COD_PRODUTO AND NFI.DSC_GRADE = PROD.DSC_GRADE
                  LEFT JOIN V_QTD_RECEBIMENTO        VQTD  ON VQTD.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                                                          AND VQTD.COD_PRODUTO = NFI.COD_PRODUTO
                                                          AND VQTD.DSC_GRADE = NFI.DSC_GRADE
                  LEFT JOIN ORDEM_SERVICO            OSREC ON OSREC.COD_OS = VQTD.COD_OS
                  LEFT JOIN PESSOA                   CONF  ON OSREC.COD_PESSOA = CONF.COD_PESSOA
				  LEFT JOIN PESSOA_FISICA 			 PF ON PF.COD_PESSOA = CONF.COD_PESSOA
                  LEFT JOIN (SELECT RC.*
                               FROM RECEBIMENTO_CONFERENCIA RC
                              INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE, COD_OS, COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO,
                                                 RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                                            FROM (SELECT CASE WHEN DTH_FINAL_ATIVIDADE IS NULL THEN TO_DATE(''31/12/9999'',''dd/mm/yyyy'')
                                                              ELSE DTH_FINAL_ATIVIDADE END AS DTH_FINAL_ATIVIDADE,
                                                         OS.COD_OS, OS.COD_RECEBIMENTO,RC.COD_PRODUTO, RC.DSC_GRADE
                                                    FROM RECEBIMENTO_CONFERENCIA RC
                                                    LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RC.COD_OS)) OS
                                 ON OS.COD_OS = RC.COD_OS
                                AND OS.RANK <= 1
                                AND OS.COD_RECEBIMENTO = RC.COD_RECEBIMENTO 
                                AND OS.COD_PRODUTO = RC.COD_PRODUTO
                                AND OS.DSC_GRADE = RC.DSC_GRADE) RC
                    ON RC.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                   AND RC.COD_PRODUTO = PROD.COD_PRODUTO
                   AND RC.DSC_GRADE = PROD.DSC_GRADE
                  LEFT JOIN MOTIVO_DIVER_RECEB       MOT   ON RC.COD_MOTIVO_DIVER_RECEB = MOT.COD_MOTIVO_DIVER_RECEB
                  LEFT JOIN V_PESO_RECEBIMENTO       VPES  ON VPES.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                                                          AND VPES.COD_PRODUTO = PROD.COD_PRODUTO
                                                          AND VPES.DSC_GRADE = PROD.DSC_GRADE
                  LEFT JOIN (SELECT P.UMA, PP.COD_PRODUTO, PP.DSC_GRADE, P.COD_RECEBIMENTO, PP.COD_NORMA_PALETIZACAO, P.COD_UNITIZADOR, P.COD_STATUS, P.COD_DEPOSITO_ENDERECO, PP.QTD
                               FROM PALETE P
                              INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA) UMA
                              ON UMA.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                             AND UMA.COD_PRODUTO = NFI.COD_PRODUTO
                             AND UMA.DSC_GRADE = NFI.DSC_GRADE
                  LEFT JOIN NORMA_PALETIZACAO        NP    ON NP.COD_NORMA_PALETIZACAO = UMA.COD_NORMA_PALETIZACAO
                  LEFT JOIN UNITIZADOR               U     ON UMA.COD_UNITIZADOR = U.COD_UNITIZADOR
                  LEFT JOIN ORDEM_SERVICO            OSUMA ON UMA.UMA = OSUMA.COD_ENDERECAMENTO
                  LEFT JOIN PESSOA                   OPEMP ON OSUMA.COD_PESSOA = OPEMP.COD_PESSOA
				  LEFT JOIN PESSOA_FISICA 			 PF_EMP ON PF_EMP.COD_PESSOA = OPEMP.COD_PESSOA
                  LEFT JOIN SIGLA                    SUMA  ON UMA.COD_STATUS = SUMA.COD_SIGLA
                  LEFT JOIN DEPOSITO_ENDERECO           DE ON UMA.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                      WHERE REC.DTH_INICIO_RECEB >= TO_DATE(''01-05-2015 00:00'', ''DD-MM-YYYY HH24:MI'')
                        AND REC.COD_STATUS = 457
                  ORDER BY REC.COD_RECEBIMENTO,
		           NF.NUM_NOTA_FISCAL,
               OSREC.DTH_FINAL_ATIVIDADE,
               OSREC.COD_OS,
               NFI.COD_PRODUTO,
               NFI.DSC_GRADE,
               UMA.COD_NORMA_PALETIZACAO,
               UMA.COD_STATUS,
               UMA.UMA';
               
END EXPORTA_RECEBIMENTO;