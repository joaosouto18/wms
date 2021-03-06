INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'enderecamento:relatorio_ocupacao-cd'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'list'), 'Relatório de Ocupação por Produtos');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'enderecamento:relatorio_ocupacao-cd') AND COD_ACAO IN (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'list')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Armazenagem' AND COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Relatórios')), 'Relatório de Ocupação de CD por Produto', 1, '#', 'S');

ALTER TABLE ETIQUETA_SEPARACAO
   ADD (DTH_GERACAO DATE);

ALTER TABLE EXPEDICAO_ANDAMENTO
   ADD (COD_BARRAS_ETIQUETA_SEPARACAO VARCHAR2(45 BYTE));

ALTER TABLE EXPEDICAO_ANDAMENTO
   ADD (COD_BARRAS_PRODUTO VARCHAR2(45 BYTE));
