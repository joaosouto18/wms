INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.20.0','01-parametro-integracao.sql');

ALTER TABLE ACAO_INTEGRACAO ADD PARAMETROS VARCHAR2(50 BYTE);

