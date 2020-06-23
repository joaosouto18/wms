INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'x.x.x', '08-alteracao-coluna-integracao.sql');

 alter table acao_integracao
add dsc_query_bkp varchar2(4000);

update acao_integracao set dsc_query_bkp = dsc_query;

update acao_integracao set dsc_query = null;

ALTER TABLE ACAO_INTEGRACAO
MODIFY (DSC_QUERY LONG NOT NULL );

update acao_integracao set dsc_query = dsc_query_bkp;

alter table acao_integracao
drop column dsc_query_bkp;

delete acao_integracao_andamento;

alter table acao_integracao_andamento
    modify (query long);