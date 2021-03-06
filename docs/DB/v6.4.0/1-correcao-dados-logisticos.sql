INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.4.0','1-correcao-dados-logisticos.sql');

/*
 * CRIA UMA TABELA ANTIGA TEMPORARIAMENTE COM O SUMARIO ANTIGO PARA COMPARAÇÃO
 */

CREATE TABLE PRODUTO_PESO_ANTIGO
(
  COD_PRODUTO VARCHAR2(20 BYTE) NOT NULL
, DSC_GRADE VARCHAR2(10 BYTE) NOT NULL
, NUM_PESO NUMBER(13, 8)
, NUM_CUBAGEM NUMBER(13, 8)
);

CREATE OR REPLACE PROCEDURE PROC_ATUALIZA_PESO_PRODUTO_OLD
(
  PRODUTO IN VARCHAR2
, GRADE   IN VARCHAR2
) AS
BEGIN

  DELETE FROM PRODUTO_PESO_ANTIGO WHERE COD_PRODUTO = PRODUTO AND DSC_GRADE = GRADE;

  INSERT INTO PRODUTO_PESO_ANTIGO (COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM)
  SELECT COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM
    FROM (SELECT P.COD_PRODUTO,
  		           P.DSC_GRADE,
		             ROUND(PDL.NUM_PESO / PE.QTD_EMBALAGEM,6) as NUM_PESO,
		             ROUND(PDL.NUM_CUBAGEM / PE.QTD_EMBALAGEM,6) as NUM_CUBAGEM
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

END PROC_ATUALIZA_PESO_PRODUTO_OLD;

/*
 * CORRIGE A PROCEDURE PARA ATUALIZAR O PESO/CUBAGEM
 */

CREATE OR REPLACE PROCEDURE PROC_ATUALIZA_PESO_PRODUTO
(
  PRODUTO IN VARCHAR2
, GRADE   IN VARCHAR2
) AS
BEGIN

  DELETE FROM PRODUTO_PESO WHERE COD_PRODUTO = PRODUTO AND DSC_GRADE = GRADE;
  INSERT INTO PRODUTO_PESO (COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM)
    SELECT COD_PRODUTO, DSC_GRADE, NUM_PESO, NUM_CUBAGEM
      FROM (SELECT COD_PRODUTO,
                   DSC_GRADE,
                   ROUND(MIN(NVL(NUM_PESO/QTD_EMBALAGEM,0)),6) as NUM_PESO ,
                   ROUND(MIN(NVL(NUM_CUBAGEM/QTD_EMBALAGEM,0)),6) as NUM_CUBAGEM
              FROM PRODUTO_EMBALAGEM
             WHERE DTH_INATIVACAO IS NULL
             GROUP BY COD_PRODUTO, DSC_GRADE
             UNION
            SELECT PV.COD_PRODUTO,
                   PV.DSC_GRADE,
		           SUM(PV.NUM_PESO) as NUM_PESO,
		           SUM(PV.NUM_CUBAGEM) as NUM_CUBAGEM
	          FROM PRODUTO_VOLUME PV
			 WHERE DTH_INATIVACAO IS NULL
           GROUP BY PV.COD_PRODUTO, PV.DSC_GRADE) P
     WHERE COD_PRODUTO = PRODUTO AND DSC_GRADE = GRADE;
END PROC_ATUALIZA_PESO_PRODUTO;

/*
 * CORRIGE A ESTRUTURA DA TABELA
 */

  ALTER TABLE PRODUTO_EMBALAGEM
    ADD (NUM_PESO_OLD NUMBER(15, 8),
	     NUM_ALTURA_OLD NUMBER(15,8),
		 NUM_LARGURA_OLD NUMBER(15,8),
		 NUM_CUBAGEM_OLD NUMBER(15,8),
		 NUM_PROFUNDIDADE_OLD NUMBER(15,8));

 UPDATE PRODUTO_EMBALAGEM
    SET NUM_PESO_OLD = NUM_PESO,
	    NUM_ALTURA_OLD = NUM_ALTURA,
		NUM_CUBAGEM_OLD = NUM_CUBAGEM,
		NUM_PROFUNDIDADE_OLD = NUM_PROFUNDIDADE,
		NUM_LARGURA_OLD = NUM_LARGURA;

 UPDATE PRODUTO_EMBALAGEM
    SET NUM_PESO = NULL,
	    NUM_ALTURA = NULL,
		NUM_CUBAGEM = NULL,
		NUM_PROFUNDIDADE = NULL,
		NUM_LARGURA = NULL;

  ALTER TABLE PRODUTO_EMBALAGEM
 MODIFY (NUM_PESO NUMBER(15, 8),
         NUM_ALTURA NUMBER(15,8),
		 NUM_CUBAGEM NUMBER(15,8),
		 NUM_PROFUNDIDADE NUMBER (15,8),
		 NUM_LARGURA NUMBER(15,8));

 UPDATE PRODUTO_EMBALAGEM
    SET NUM_PESO = NUM_PESO_OLD,
	    NUM_ALTURA = NUM_ALTURA_OLD,
		NUM_CUBAGEM = NUM_CUBAGEM_OLD,
		NUM_PROFUNDIDADE = NUM_PROFUNDIDADE_OLD,
		NUM_LARGURA = NUM_LARGURA_OLD;

  ALTER TABLE PRODUTO_EMBALAGEM
   DROP (NUM_PESO_OLD,
         NUM_ALTURA_OLD,
		 NUM_CUBAGEM_OLD,
		 NUM_PROFUNDIDADE_OLD,
		 NUM_LARGURA_OLD);

/*
 * ATUALIZA O PESO DOS PRODUTOS
 */

 MERGE INTO PRODUTO_EMBALAGEM  PE
 USING (SELECT P.COD_PRODUTO,
   		       P.DSC_GRADE,
	 	       ROUND(PDL.NUM_PESO / PE.QTD_EMBALAGEM,6) as NUM_PESO,
		       ROUND(PDL.NUM_ALTURA / PE.QTD_EMBALAGEM,6) as NUM_ALTURA,
               PDL.NUM_PROFUNDIDADE,
               PDL.NUM_LARGURA
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
        INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM) R
	ON (R.COD_PRODUTO = PE.COD_PRODUTO AND R.DSC_GRADE = PE.DSC_GRADE)
  WHEN MATCHED THEN UPDATE SET PE.NUM_PESO = PE.QTD_EMBALAGEM * R.NUM_PESO,
                               PE.NUM_ALTURA = PE.QTD_EMBALAGEM * R.NUM_ALTURA,
                               PE.NUM_PROFUNDIDADE = R.NUM_PROFUNDIDADE,
                               PE.NUM_LARGURA = R.NUM_LARGURA;

UPDATE PRODUTO_EMBALAGEM SET NUM_CUBAGEM = NUM_ALTURA * NUM_PROFUNDIDADE * NUM_LARGURA;

/*
 * ATUALIZA OS DOIS SUMARIOS, O ANTIGO E O ATUAL
 */

BEGIN
   FOR c IN (SELECT COD_PRODUTO, DSC_GRADE FROM PRODUTO) LOOP
       PROC_ATUALIZA_PESO_PRODUTO_OLD(c.COD_PRODUTO, c.DSC_GRADE);
   END LOOP;
END;

BEGIN
   FOR c IN (SELECT COD_PRODUTO, DSC_GRADE FROM PRODUTO) LOOP
       PROC_ATUALIZA_PESO_PRODUTO(c.COD_PRODUTO, c.DSC_GRADE);
   END LOOP;
END;

/*
 * DEIXO APENAS UM ENDEREÇO DE PICKING PARA TODAS AS EMBALAGENS DO PRODUTO
 */
MERGE INTO PRODUTO_EMBALAGEM PE
USING (SELECT PE.COD_PRODUTO, PE.DSC_GRADE, PE.COD_DEPOSITO_ENDERECO
         FROM PRODUTO_EMBALAGEM PE
        INNER JOIN (SELECT COD_PRODUTO, DSC_GRADE, MIN(COD_PRODUTO_EMBALAGEM) as COD_PRODUTO_EMBALAGEM
                      FROM PRODUTO_EMBALAGEM
                     WHERE COD_DEPOSITO_ENDERECO IS NOT NULL
                       AND DTH_INATIVACAO IS NULL
                     GROUP BY COD_PRODUTO, DSC_GRADE) M ON M.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM ) R
	ON (R.COD_PRODUTO = PE.COD_PRODUTO AND R.DSC_GRADE = PE.DSC_GRADE)
  WHEN MATCHED THEN UPDATE SET PE.COD_DEPOSITO_ENDERECO = R.COD_DEPOSITO_ENDERECO;

