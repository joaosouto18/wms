INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.8.0', '01-integracao-finalizacao-recebimento.sql');

insert into parametro (cod_parametro,cod_contexto_parametro,dsc_parametro,dsc_titulo_parametro,ind_parametro_sistema,cod_tipo_atributo,dsc_valor_parametro)
    values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'PARÂMETROS DE INTEGRAÇÃO'), 'UTILIZA_INTEGRACAO_RECEBIMENTO_ERP','Dispara integração de retorno genérico de conferencia de recebimento para o ERP','S','A','N');

insert into parametro (cod_parametro,cod_contexto_parametro,dsc_parametro,dsc_titulo_parametro,ind_parametro_sistema,cod_tipo_atributo,dsc_valor_parametro)
    values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'PARÂMETROS DE INTEGRAÇÃO'), 'ID_INTEGRACAO_FINALIZA_RECEBIMENTO_ERP','ID integração de retorno genérico de conferencia de recebimento para o ERP','S','A','');

INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA,COD_REFERENCIA_SIGLA) VALUES (630,79,'FINALIZACAO RECEBIMENTO - RECEBIMENTO ERP','B');
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA,COD_REFERENCIA_SIGLA) VALUES (631,79,'FINALIZACAO RECEBIMENTO - NOTA FISCAL','B');
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA,COD_REFERENCIA_SIGLA) VALUES (632,79,'FINALIZACAO RECEBIMENTO - ITEM RECEBIMENTO ERP','B');
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA,COD_REFERENCIA_SIGLA) VALUES (633,79,'FINALIZACAO RECEBIMENTO - ITEM NOTA FISCAL','B');