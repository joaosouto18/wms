INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.16.0','xx-ajustes-wilso.sql');

ALTER TABLE NOTA_FISCAL
  ADD (COD_TIPO_NOTA_FISCAL NUMBER(13,8));

INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA,DSC_TIPO_SIGLA,IND_SIGLA_SISTEMA) VALUES (80,'TIPO NOTA FISCAL','S');
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA) VALUES (622,80,'ENTRADA_FORNECEDOR');
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA) VALUES (623,80,'DEVOLUCAO_CLIENTE');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'RECEBIMENTO'), 'ATUALIZAR_DATA_PICKING', 'Atualizar data do Picking em novos recebimentos de devolução(S/N)','N','A','N');

ALTER TABLE RECEBIMENTO_VOLUME
  ADD (QTD_CONFERIDA_BLOQUEADA NUMBER(13,5) DEFAULT 0);

ALTER TABLE RECEBIMENTO_EMBALAGEM
  ADD (QTD_CONFERIDA_BLOQUEADA NUMBER(13,5) DEFAULT 0);

INSERT INTO ACAO (COD_ACAO,DSC_ACAO,NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL,'Recebimentos Bloqueados','visualizar-recebimentos-bloqueados');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO,COD_RECURSO,COD_ACAO,DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL,(SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'visualizar-recebimentos-bloqueados'), 'Recebimentos bloqueados por data de validade');
INSERT INTO MENU_ITEM (COD_MENU_ITEM,COD_RECURSO_ACAO,COD_PAI,DSC_MENU_ITEM,NUM_PESO,DSC_URL,DSC_TARGET,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento') AND COD_ACAO IN (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'visualizar-recebimentos-bloqueados')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Recebimento' AND COD_PAI = 0), 'Recebimentos Bloqueados', 11, '#', '_self', 'S');


