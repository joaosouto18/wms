INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','1 dthFimConferencia_ApontamentoSeparacaoMapa.sql');

ALTER TABLE APONTAMENTO_SEPARACAO_MAPA
  ADD (DTH_FIM_CONFERENCIA DATE);

 INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0, (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Relatórios'),'Produtividade',5,'#','_self','S');
UPDATE MENU_ITEM SET COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Produtividade'), NUM_PESO = 1 WHERE DSC_MENU_ITEM LIKE 'Apontamento de Produtividade';
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Produtividade Detalhada','relatorio-detalhado');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL,(SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'produtividade:relatorio_indicadores') ,(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'relatorio-detalhado'), 'Apontamento de Produtividade Detalhado');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where DSC_RECURSO_ACAO LIKE 'Apontamento de Produtividade Detalhado'), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Produtividade'),'Produtividade Detalhada',2,'#','_self','S');