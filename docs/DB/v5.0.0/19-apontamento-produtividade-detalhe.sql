/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 07/06/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','apontamentoProdutividadeDetalhe.sql');

/*
 * CRIAÇÃO DA TABELA SUMÁRIO AONDE TEREMOS O RESUMO DA PRODUTIVIDADE AGRUPADA POR FUNCIONÁRIO E DATA
 */
CREATE TABLE PRODUTIVIDADE_DETALHE (
	DSC_ATIVIDADE VARCHAR2(100 BYTE),
        IDENTIDADE    VARCHAR2(100 BYTE),
	COD_PESSOA    NUMBER,
	COD_PRODUTO   NUMBER,
	DTH_INICIO    DATE,
	DTH_FIM       DATE,
	QTD_PRODUTOS  NUMBER,
	QTD_VOLUMES   NUMBER,
	QTD_CUBAGEM   NUMBER,
	QTD_PESO      NUMBER,
	QTD_PALETES   NUMBER,
        DSC_GRADE     VARCHAR2(10 BYTE),
        QTD_CARGA     NUMBER);


create or replace PROCEDURE \PROC_PRODUTIVIDADE_DETALHE (DTH_INICIAL IN VARCHAR2, DTH_FINAL IN VARCHAR2) AS
BEGIN
    DECLARE DTH_INICIO_PARAM VARCHAR2(100);
    DTH_FIM_PARAM VARCHAR2(100);
BEGIN

  if DTH_INICIAL = 'ONTEM' THEN
     DTH_INICIO_PARAM := TO_CHAR(SYSDATE() -1,'DD/MM/YYYY');
  ELSE
     DTH_INICIO_PARAM := DTH_INICIAL;
  END IF;

  if DTH_FINAL = 'ONTEM' THEN
     DTH_FIM_PARAM := TO_CHAR(SYSDATE,'DD/MM/YYYY');
  ELSE
     DTH_FIM_PARAM := DTH_FINAL;
  END IF;

  DELETE FROM PRODUTIVIDADE_DETALHE
   WHERE TO_DATE(TO_CHAR(DTH_INICIO,'DD/MM/YYYY'),'DD/MM/YYYY')
    BETWEEN TO_DATE(DTH_INICIO_PARAM,'DD/MM/YYYY') AND TO_DATE(DTH_FIM_PARAM,'DD/MM/YYYY');

INSERT INTO PRODUTIVIDADE_DETALHE (DSC_ATIVIDADE, IDENTIDADE, COD_PESSOA, COD_PRODUTO, DSC_GRADE, DTH_INICIO , DTH_FIM , QTD_PRODUTOS, QTD_VOLUMES, QTD_CUBAGEM, QTD_PESO, QTD_PALETES, QTD_CARGA)

SELECT DSC_ATIVIDADE, IDENTIDADE, COD_PESSOA, COD_PRODUTO, DSC_GRADE, DTH_INICIO ,  DTH_FIM , QTD_PRODUTOS, QTD_VOLUMES, QTD_CUBAGEM, QTD_PESO, QTD_PALETES, QTD_CARGA
FROM (  
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE SEPARACAO
	 */
	SELECT 
		'SEPARACAO' as DSC_ATIVIDADE,
		 TO_CHAR(A.COD_MAPA_SEPARACAO) AS IDENTIDADE,
		 A.COD_USUARIO as COD_PESSOA,
		 M.COD_PRODUTO,
		 M.DSC_GRADE,
		 TO_DATE(TO_CHAR(A.DTH_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
		 TO_DATE(TO_CHAR(A.DTH_FIM_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		 COUNT(DISTINCT (M.COD_PRODUTO || M.DSC_GRADE)) as QTD_PRODUTOS,
		 SUM(M.QTD_SEPARAR) as QTD_VOLUMES,
		 SUM(NVL(NVL(SPP.NUM_CUBAGEM, PV.NUM_CUBAGEM), 0) * (M.QTD_EMBALAGEM * M.QTD_SEPARAR) - NVL(M.QTD_CORTADO,0)) as QTD_CUBAGEM,
		 SUM(NVL(NVL(SPP.NUM_PESO, PV.NUM_PESO), 0) * (M.QTD_EMBALAGEM * M.QTD_SEPARAR) - NVL(M.QTD_CORTADO,0)) as QTD_PESO,
		 0 as QTD_PALETES,
		 1 as QTD_CARGA
	FROM APONTAMENTO_SEPARACAO_MAPA A
 INNER JOIN MAPA_SEPARACAO_PRODUTO M ON A.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
  LEFT JOIN PRODUTO_PESO SPP ON SPP.COD_PRODUTO = M.COD_PRODUTO AND SPP.DSC_GRADE = M.DSC_GRADE AND M.COD_PRODUTO_EMBALAGEM IS NOT NULL
  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = M.COD_PRODUTO_VOLUME
  WHERE (M.QTD_EMBALAGEM * M.QTD_SEPARAR) - NVL(M.QTD_CORTADO,0) > 0
	GROUP BY
		A.COD_USUARIO,
		M.COD_PRODUTO,
		TO_DATE(TO_CHAR(A.DTH_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		TO_DATE(TO_CHAR(A.DTH_FIM_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		A.COD_MAPA_SEPARACAO,
                M.DSC_GRADE
			
	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE SEPARACAO ETIQUETA
	 */
	SELECT
       DISTINCT 'SEPARACAO' as DSC_ATIVIDADE,
       TO_CHAR(EM.COD_EXPEDICAO) AS IDENTIDADE,
       EQ.COD_USUARIO  as COD_PESSOA,
       ES.COD_PRODUTO,
       ES.DSC_GRADE,
       TO_DATE(TO_CHAR(EQ.DTH_VINCULO,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
       TO_DATE(TO_CHAR(EQ.DTH_VINCULO,'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
       ES.QTD_PRODUTOS,
       1 as QTD_VOLUME,
       (NVL(PV.NUM_PESO,PE.NUM_PESO) / ES.QTD_USUARIO) as QTD_PESO,
       (NVL(PV.NUM_CUBAGEM,PE.NUM_CUBAGEM) / ES.QTD_USUARIO) as QTD_CUBAGEM,
       0 as QTD_PALETES,
		   1 as QTD_CARGA
  FROM EQUIPE_SEPARACAO EQ
  LEFT JOIN (SELECT COUNT(DISTINCT(EQ.COD_USUARIO)) as QTD_USUARIO,
                    ES.COD_ETIQUETA_SEPARACAO,
                    ES.COD_PRODUTO_VOLUME,
                    ES.COD_PRODUTO_EMBALAGEM,
                    ES.QTD_EMBALAGEM,
                    ES.COD_PRODUTO,
                    ES.DSC_GRADE,
                    COUNT(DISTINCT (ES.COD_PRODUTO || ES.DSC_GRADE)) as QTD_PRODUTOS,
                    ES.COD_ETIQUETA_MAE
               FROM EQUIPE_SEPARACAO EQ
              INNER JOIN ETIQUETA_SEPARACAO ES ON ES.COD_ETIQUETA_SEPARACAO BETWEEN EQ.ETIQUETA_INICIAL AND EQ.ETIQUETA_FINAL
              GROUP BY ES.COD_ETIQUETA_MAE, ES.COD_ETIQUETA_SEPARACAO, ES.COD_PRODUTO_VOLUME, ES.COD_PRODUTO_EMBALAGEM,QTD_EMBALAGEM, ES.COD_PRODUTO, ES.DSC_GRADE) ES
    ON ES.COD_ETIQUETA_SEPARACAO BETWEEN EQ.ETIQUETA_INICIAL AND EQ.ETIQUETA_FINAL
  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ES.COD_PRODUTO_VOLUME
  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
  LEFT JOIN ETIQUETA_MAE EM ON ES.COD_ETIQUETA_MAE = EM.COD_ETIQUETA_MAE

	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE CONF. RECEBIMENTO
	 */			
	SELECT 
		'CONF. RECEBIMENTO' as DSC_ATIVIDADE,
		TO_CHAR(V.COD_RECEBIMENTO) AS IDENTIDADE,
		P.COD_PESSOA as COD_PESSOA,
		V.COD_PRODUTO,
		V.DSC_GRADE,
		TO_DATE(TO_CHAR(OS.DTH_INICIO_ATIVIDADE, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
		TO_DATE(TO_CHAR(OS.DTH_FINAL_ATIVIDADE, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		COUNT(DISTINCT V.COD_PRODUTO || '/' || V.DSC_GRADE) as QTD_PRODUTOS,
		SUM(PROD.NUM_VOLUMES * V.QTD) as QTD_VOLUMES,
		SUM(NVL(PP.NUM_CUBAGEM, 0) * V.QTD) as QTD_CUBAGEM,
		SUM(NVL(PP.NUM_PESO, 0) * V.QTD) as QTD_PESO,
		0 as QTD_PALETES,
		COUNT(DISTINCT V.COD_RECEBIMENTO) as QTD_CARGA
	FROM 
		V_QTD_RECEBIMENTO V LEFT JOIN 
		ORDEM_SERVICO OS ON OS.COD_OS = V.COD_OS LEFT JOIN 
		PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA LEFT JOIN 
		PRODUTO PROD ON PROD.COD_PRODUTO = V.COD_PRODUTO AND PROD.DSC_GRADE = V.DSC_GRADE LEFT JOIN 
		PRODUTO_PESO PP ON PP.COD_PRODUTO = V.COD_PRODUTO AND V.DSC_GRADE = V.DSC_GRADE
	GROUP BY 
		P.COD_PESSOA, 
		V.COD_PRODUTO, 
		TO_DATE(TO_CHAR(OS.DTH_INICIO_ATIVIDADE, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'), 
		TO_DATE(TO_CHAR(OS.DTH_FINAL_ATIVIDADE, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		V.COD_RECEBIMENTO,
                V.DSC_GRADE
		
	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE ENDERECAMENTO
	 */		
	SELECT 
		'ENDERECAMENTO' as DSC_ATIVIDADE,
		TO_CHAR(P.COD_RECEBIMENTO) as IDENTIDADE,
		RE.COD_USUARIO_ATENDIMENTO as COD_PESSOA ,
		PP.COD_PRODUTO,
		PP.DSC_GRADE,
		TO_DATE(TO_CHAR(RE.DTH_RESERVA, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
		TO_DATE(TO_CHAR(RE.DTH_ATENDIMENTO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		COUNT (DISTINCT PP.COD_PRODUTO || '/' || PP.DSC_GRADE) as QTD_PRODUTOS,
		SUM(PP.QTD) as QTD_VOLUMES,
		SUM(NVL(NVL(SPP.NUM_CUBAGEM,0), PV.NUM_CUBAGEM) * PP.QTD) as QTD_CUBAGEM,
		SUM(NVL(NVL(SPP.NUM_PESO,0), PV.NUM_PESO) * PP.QTD) as QTD_PESO,
		COUNT (DISTINCT P.UMA) as QTD_PALETES,
		0 as QTD_CARGA
	FROM 
		PALETE P INNER JOIN 
		PALETE_PRODUTO PP ON P.UMA = PP.UMA LEFT JOIN 
		PRODUTO_PESO SPP ON SPP.COD_PRODUTO = PP.COD_PRODUTO AND SPP.DSC_GRADE = PP.DSC_GRADE AND PP.COD_PRODUTO_VOLUME IS NULL LEFT JOIN 
		PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = PP.COD_PRODUTO_VOLUME INNER JOIN 
		RESERVA_ESTOQUE_ENDERECAMENTO REE ON REE.UMA = P.UMA INNER JOIN 
		RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
	WHERE 
		P.COD_STATUS = 536 AND 
		RE.DTH_ATENDIMENTO IS NOT NULL AND 
		RE.IND_ATENDIDA = 'S'
	GROUP BY 
		RE.COD_USUARIO_ATENDIMENTO,
		PP.COD_PRODUTO,		
		TO_DATE(TO_CHAR(RE.DTH_RESERVA, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		TO_DATE(TO_CHAR(RE.DTH_ATENDIMENTO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		P.COD_RECEBIMENTO,
                PP.DSC_GRADE
		
	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE DESCARREGAMENTO
	 */
	SELECT 
		'DESCARREGAMENTO' as DSC_ATIVIDADE,
		TO_CHAR(RD.COD_RECEBIMENTO) as IDENTIDADE,
		RD.COD_USUARIO as COD_PESSOA,
		PP.COD_PRODUTO,
		PP.DSC_GRADE,
		TO_DATE(TO_CHAR(RD.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
		TO_DATE(TO_CHAR(RD.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		CAST(SUM(V.QTD/QTD_PES.QTD_USUARIOS)as NUMBER(30,2)) as QTD_PRODUTOS,
		CAST(SUM((V.QTD * P.NUM_VOLUMES)/QTD_PES.QTD_USUARIOS) as NUMBER(20,2)) as QTD_VOLUMES,
		CAST(SUM((V.QTD * NVL(NVL(PP.NUM_CUBAGEM,0),0))/QTD_PES.QTD_USUARIOS) as NUMBER(30,2)) as QTD_CUBAGEM,
		CAST(SUM((V.QTD * NVL(NVL(PP.NUM_PESO,0),0))/QTD_PES.QTD_USUARIOS)    as NUMBER(30,2)) as QTD_PESO,
		CAST(SUM(NVL(UMA.QTD_PALETE,0)/QTD_PES.QTD_USUARIOS)    as NUMBER(30,2)) as QTD_PALETE,
		0 as QTD_CARGA
	FROM 
		RECEBIMENTO_DESCARGA RD LEFT JOIN 
		(
			SELECT 
				COD_RECEBIMENTO, 
				COUNT(COD_USUARIO) as QTD_USUARIOS
			FROM 
				RECEBIMENTO_DESCARGA
			GROUP BY 
				COD_RECEBIMENTO
		) 
		QTD_PES ON QTD_PES.COD_RECEBIMENTO = RD.COD_RECEBIMENTO LEFT JOIN 
		V_QTD_RECEBIMENTO V ON V.COD_RECEBIMENTO = RD.COD_RECEBIMENTO LEFT JOIN 
		PRODUTO P ON P.COD_PRODUTO = V.COD_PRODUTO AND P.DSC_GRADE = V.DSC_GRADE LEFT JOIN 
		(
			SELECT 
				COUNT(UMA) as QTD_PALETE, 
				COD_RECEBIMENTO 
			FROM 
				PALETE 
			GROUP BY 
				COD_RECEBIMENTO
		) 
		UMA ON UMA.COD_RECEBIMENTO = RD.COD_RECEBIMENTO LEFT JOIN 
		PRODUTO_PESO PP ON PP.COD_PRODUTO = V.COD_PRODUTO AND PP.DSC_GRADE = V.DSC_GRADE
	GROUP BY 
		RD.COD_USUARIO, 
		TO_DATE(TO_CHAR(RD.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		RD.COD_RECEBIMENTO,
		PP.COD_PRODUTO,
                PP.DSC_GRADE
		
	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE CARREGAMENTO
	 */	
	SELECT 
		'CARREGAMENTO' as DSC_ATIVIDADE,
		TO_CHAR(E.COD_EXPEDICAO) as IDENTIDADE,
		E.COD_USUARIO as COD_PESSOA,
		PES.COD_PRODUTO,
		PES.DSC_GRADE,
		TO_DATE(TO_CHAR(E.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
		TO_DATE(TO_CHAR(E.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		COUNT(DISTINCT(PP.COD_PRODUTO || PP.DSC_GRADE)) as QTD_PRODUTOS,
		SUM(NVL(PP.QTD_ATENDIDA,0) * NVL(PROD.NUM_VOLUMES,0)/QTD_PES.QTD_USUARIOS) as QTD_VOLUMES,
		SUM(NVL(PP.QTD_ATENDIDA,0) * NVL(PES.NUM_CUBAGEM,0) /QTD_PES.QTD_USUARIOS) as QTD_CUBAGEM,
		SUM(NVL(PP.QTD_ATENDIDA,0) * NVL(PES.NUM_PESO,0)/QTD_PES.QTD_USUARIOS) as QTD_PESO,
		0 as QTD_PALETES,
		0 as QTD_CARGA
	FROM 
		EQUIPE_CARREGAMENTO_EXPEDICAO E LEFT JOIN 
		(
			SELECT 
				COD_EXPEDICAO, 
				COUNT(COD_USUARIO) as QTD_USUARIOS
			FROM 
				EQUIPE_CARREGAMENTO_EXPEDICAO
			GROUP BY 
				COD_EXPEDICAO
		) 
		QTD_PES ON QTD_PES.COD_EXPEDICAO = E.COD_EXPEDICAO LEFT JOIN 
		CARGA C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO LEFT JOIN 
		PEDIDO P ON P.COD_CARGA = C.COD_CARGA LEFT JOIN 
		PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO LEFT JOIN 
		PRODUTO_PESO PES ON PES.COD_PRODUTO = PP.COD_PRODUTO AND PES.DSC_GRADE = PP.DSC_GRADE LEFT JOIN 
		PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
	GROUP BY 
		E.COD_USUARIO,
		TO_DATE(TO_CHAR(E.DTH_VINCULO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		PES.COD_PRODUTO,
		E.COD_EXPEDICAO,
                PES.DSC_GRADE

	UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE CONF. SEPARACAO
	 */		
	SELECT 
		'CONF. SEPARACAO' as DSC_ATIVIDADE,
		TO_CHAR(MSC.COD_MAPA_SEPARACAO) AS IDENTIDADE,
		OS.COD_PESSOA as COD_PESSOA,
		SPP.COD_PRODUTO,
		SPP.DSC_GRADE,
		TO_DATE(TO_CHAR(MSC.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_INICIO,
		TO_DATE(TO_CHAR(MSC.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
		COUNT( DISTINCT (MSC.COD_PRODUTO || '/'|| MSC.DSC_GRADE)) as QTD_PRODUTOS,
		SUM(MSC.QTD_CONFERIDA) as QTD_VOLUMES,
		SUM(MSC.QTD_CONFERIDA * NVL(SPP.NUM_CUBAGEM,0)) as CUBAGEM,
		SUM(MSC.QTD_CONFERIDA * NVL(SPP.NUM_PESO,0)) as QTD_PESO,
		0 as QTD_PALETES,
		NVL(COUNT( DISTINCT C.COD_CARGA),0) / COUNT(DISTINCT OS.COD_PESSOA) as QTD_CARGA
	FROM 
		MAPA_SEPARACAO_CONFERENCIA MSC INNER JOIN 
		MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO INNER JOIN 
		EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO INNER JOIN 
		CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO LEFT JOIN 
		PRODUTO_PESO SPP ON MSC.COD_PRODUTO = SPP.COD_PRODUTO AND MSC.DSC_GRADE = SPP.DSC_GRADE LEFT JOIN 
		ORDEM_SERVICO OS ON OS.COD_OS = MSC.COD_OS
	GROUP BY 
		OS.COD_PESSOA,
		TO_DATE(TO_CHAR(MSC.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS'),
		MSC.COD_MAPA_SEPARACAO,
		SPP.COD_PRODUTO,
                SPP.DSC_GRADE

        UNION
	/*
	 * SCRIPT PARA QUE SELECIONA ATIVIDADE DE RESSUPRIMENTO
	 */
        SELECT 
            'RESSUPRIMENTO' as DSC_ATIVIDADE,
            TO_CHAR(ORO.COD_ONDA_RESSUPRIMENTO_OS) as IDENTIDADE,
            RE.COD_USUARIO_ATENDIMENTO as COD_PESSOA,
            OROP.COD_PRODUTO,
            OROP.DSC_GRADE,
            TO_DATE(TO_CHAR(RE.DTH_ATENDIMENTO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS')  as DTH_INICIO,
            TO_DATE(TO_CHAR(RE.DTH_ATENDIMENTO, 'DD/MM/YYYY HH24:MI:SS'),'DD/MM/YYYY HH24:MI:SS') as DTH_FIM,
            COUNT(DISTINCT OROP.COD_PRODUTO || '/' || OROP.DSC_GRADE) as QTD_PRODUTOS,
            SUM(OROP.QTD) as QTD_VOLUMES,
            SUM(NVL(NVL(SPP.NUM_CUBAGEM,PV.NUM_CUBAGEM),0) * OROP.QTD) as QTD_CUBAGEM,
            SUM(NVL(NVL(SPP.NUM_PESO,PV.NUM_PESO),0) * OROP.QTD) as QTD_PESO,
            COUNT(DISTINCT ORO.COD_ONDA_RESSUPRIMENTO_OS) as QTD_PALETES,
            0 as QTD_CARGA
        FROM ONDA_RESSUPRIMENTO OND
  INNER JOIN ONDA_RESSUPRIMENTO_OS ORO ON ORO.COD_ONDA_RESSUPRIMENTO = OND.COD_ONDA_RESSUPRIMENTO
  INNER JOIN RESERVA_ESTOQUE_ONDA_RESSUP REOS ON REOS.COD_ONDA_RESSUPRIMENTO_OS = ORO.COD_ONDA_RESSUPRIMENTO_OS
  INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REOS.COD_RESERVA_ESTOQUE
  INNER JOIN ONDA_RESSUPRIMENTO_OS_PRODUTO OROP ON OROP.COD_ONDA_RESSUPRIMENTO_OS = ORO.COD_ONDA_RESSUPRIMENTO_OS
   LEFT JOIN PRODUTO_PESO SPP ON SPP.COD_PRODUTO = OROP.COD_PRODUTO AND SPP.DSC_GRADE = OROP.DSC_GRADE AND OROP.COD_PRODUTO_VOLUME IS NULL
   LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = OROP.COD_PRODUTO_VOLUME
        WHERE 
            ORO.COD_STATUS = 541
            AND RE.TIPO_RESERVA = 'S'
        GROUP BY 
            ORO.COD_ONDA_RESSUPRIMENTO_OS,
            RE.COD_USUARIO_ATENDIMENTO,
            RE.DTH_ATENDIMENTO,
            OROP.COD_PRODUTO,
            OROP.DSC_GRADE
		
)
  WHERE 
    TO_DATE(TO_CHAR(DTH_INICIO,'DD/MM/YYYY'),'DD/MM/YYYY') BETWEEN 
    TO_DATE(DTH_INICIO_PARAM,'DD/MM/YYYY') AND 
    TO_DATE(DTH_FIM_PARAM,'DD/MM/YYYY');

END;
END PROC_PRODUTIVIDADE_DETALHE;

/*
 * SCRIPT PARA CRIAÇÃO DO JOB QUE IRÁ ATUALIZAR DIARIAMENTE A TABELA SUMÁRIO
 */
BEGIN
    DBMS_SCHEDULER.CREATE_JOB (
            job_name => '"JOB_PRODUTIVIDADE_DETALHE"',
            job_type => 'STORED_PROCEDURE',
            job_action => 'PROC_PRODUTIVIDADE_DETALHE',
            number_of_arguments => 2,
            start_date => NULL,
            repeat_interval => 'FREQ=DAILY;BYDAY=MON,TUE,WED,THU,FRI,SAT,SUN;BYHOUR=4;BYMINUTE=0;BYSECOND=0',
            end_date => NULL,
            enabled => FALSE,
            auto_drop => FALSE,
            comments => '');

    DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(
             job_name => '"JOB_PRODUTIVIDADE_DETALHE"',
             argument_position => 1,
             argument_value => 'ONTEM');
    DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(
             job_name => '"JOB_PRODUTIVIDADE_DETALHE"',
             argument_position => 2,
             argument_value => 'ONTEM');

    DBMS_SCHEDULER.SET_ATTRIBUTE(
             name => '"JOB_APONTAMENTO_PRODUTIVIDADE"',
             attribute => 'logging_level', value => DBMS_SCHEDULER.LOGGING_OFF);

    DBMS_SCHEDULER.enable(
             name => '"JOB_PRODUTIVIDADE_DETALHE"');
END;

/*
 * ATUALIZA O SUMÁRIO COM OS LANÇAMENTOS ATUAIS
 */
EXECUTE PROC_PRODUTIVIDADE_DETALHE('01/01/2000','01/02/2999');