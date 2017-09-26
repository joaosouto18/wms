/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 19/09/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','xx-cadastro-peso-embalagem.sql');

ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_ALTURA NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM  
  ADD NUM_CUBAGEM NUMBER(16,4) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_PROFUNDIDADE NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_LARGURA NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_PESO NUMBER(15,3) DEFAULT 0 NULL;

CREATE OR REPLACE PROCEDURE PROC_ATUALIZA_PESO_PRODUTO
(
  PRODUTO IN VARCHAR2
, GRADE   IN VARCHAR2
) AS
BEGIN

  DELETE FROM PRODUTO_PESO WHERE COD_PRODUTO = PRODUTO AND DSC_GRADE = GRADE;

  INSERT INTO PRODUTO_PESO (COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM)
  SELECT COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM
    FROM (SELECT P.COD_PRODUTO,
  		           P.DSC_GRADE,
		             ROUND(PE.NUM_PESO / PE.QTD_EMBALAGEM,6) as NUM_PESO,
		             ROUND(PE.NUM_CUBAGEM / PE.QTD_EMBALAGEM,6) as NUM_CUBAGEM
            FROM (SELECT PE.COD_PRODUTO, PE.DSC_GRADE, MIN(PDL.COD_PRODUTO_DADO_LOGISTICO) as COD_PRODUTO_DADO_LOGISTICO
  		              FROM (SELECT MIN(PE.COD_PRODUTO_EMBALAGEM) AS COD_PRODUTO_EMBALAGEM, PE.COD_PRODUTO,PE.DSC_GRADE
  				                  FROM PRODUTO_EMBALAGEM PE
                           INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
  				                 INNER JOIN (SELECT MIN(QTD_EMBALAGEM) AS FATOR, COD_PRODUTO, DSC_GRADE
  								                       FROM PRODUTO_EMBALAGEM PE
                                        INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
  							                        GROUP BY COD_PRODUTO,DSC_GRADE) PEM
  					                  ON (PEM.COD_PRODUTO = PE.COD_PRODUTO)
                             AND (PEM.DSC_GRADE = PE.DSC_GRADE)
                             AND (PEM.FATOR = PE.QTD_EMBALAGEM)
                           GROUP BY PE.COD_PRODUTO, PE.DSC_GRADE) PE
		               INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
		               GROUP BY COD_PRODUTO, DSC_GRADE) P
           INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_DADO_LOGISTICO = P.COD_PRODUTO_DADO_LOGISTICO
           INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
           UNION
          SELECT PV.COD_PRODUTO,
		             PV.DSC_GRADE,
		             SUM(PV.NUM_PESO) as NUM_PESO,
		             SUM(PV.NUM_CUBAGEM) as NUM_CUBAGEM
	          FROM PRODUTO_VOLUME PV
           GROUP BY PV.COD_PRODUTO, PV.DSC_GRADE) P
     WHERE COD_PRODUTO = PRODUTO AND DSC_GRADE = GRADE;

END PROC_ATUALIZA_PESO_PRODUTO;