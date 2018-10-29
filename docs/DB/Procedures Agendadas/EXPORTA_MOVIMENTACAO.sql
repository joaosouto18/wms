create or replace PROCEDURE EXPORTA_MOVIMENTACAO AS 
BEGIN

execute immediate 'DROP TABLE EXPORTACAO_MOVIMENTACAO ';

execute immediate 'CREATE TABLE EXPORTACAO_MOVIMENTACAO as
  SELECT HIST.COD_PRODUTO as "COD_PRODUTO",
                          HIST.DSC_GRADE as "GRADE",
                          PROD.DSC_PRODUTO "PRODUTO",
                          CASE WHEN HIST.QTD >= 0 THEN ''ENTRADA''
                               WHEN HIST.QTD < 0 THEN ''SAIDA''
                          END as "TIPO",
                          HIST.QTD as "QTD",
                          DEP.DSC_DEPOSITO_ENDERECO as "ENDERECO",
                          TO_CHAR(HIST.DTH_MOVIMENTACAO,''DD/MM/YYYY HH24:MI:SS'')as "DTH_MOVIMENTACAO",
                          PES.NOM_PESSOA as "PESSOA",
                          HIST.OBSERVACAO as "OBSERVACAO",
                          P.UMA as "PALETE",
                          U.DSC_UNITIZADOR as "UNITIZADOR",
                          HIST.COD_OS as "OS",
                          OS.COD_RECEBIMENTO as "RECEBIMENTO",
						  PF.NUM_CPF AS CPF_USUARIO
                     FROM HISTORICO_ESTOQUE HIST
               INNER JOIN PRODUTO PROD ON (HIST.COD_PRODUTO = PROD.COD_PRODUTO AND HIST.DSC_GRADE = PROD.DSC_GRADE)
               INNER JOIN DEPOSITO_ENDERECO DEP ON HIST.COD_DEPOSITO_ENDERECO = DEP.COD_DEPOSITO_ENDERECO
                LEFT JOIN PESSOA PES ON HIST.COD_PESSOA = PES.COD_PESSOA
			    LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = PES.COD_PESSOA
                LEFT JOIN ORDEM_SERVICO OS ON HIST.COD_OS = OS.COD_OS
                LEFT JOIN PALETE P ON OS.COD_ENDERECAMENTO = P.UMA
                LEFT JOIN UNITIZADOR U ON U.COD_UNITIZADOR = P.COD_UNITIZADOR
                    WHERE HIST.DTH_MOVIMENTACAO >= TO_DATE(''01-05-2015 00:00'', ''DD-MM-YYYY HH24:MI'')
                 GROUP BY
                       HIST.COD_PRODUTO ,
                          HIST.DSC_GRADE ,
                          PROD.DSC_PRODUTO ,
                          HIST.QTD ,
                          DEP.DSC_DEPOSITO_ENDERECO ,
                          HIST.DTH_MOVIMENTACAO,
                          PES.NOM_PESSOA ,
                          HIST.OBSERVACAO ,
                          P.UMA ,
                          U.DSC_UNITIZADOR ,
                          HIST.COD_OS ,
                          OS.COD_RECEBIMENTO,
						  PF.NUM_CPF
                 ORDER BY HIST.DTH_MOVIMENTACAO, HIST.COD_PRODUTO, HIST.DSC_GRADE, HIST.QTD';

END EXPORTA_MOVIMENTACAO;