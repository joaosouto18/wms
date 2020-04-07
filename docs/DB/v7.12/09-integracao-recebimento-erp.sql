INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.12', '09-integracao-recebimento-erp.sql');

insert into parametro values (SQ_PARAMETRO_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'PARÂMETROS DE INTEGRAÇÃO'), 'COD_INTEGRACAO_RECEBIMENTO_ERP','Código integração recebimento ERP', 'S','A','10,11,12');