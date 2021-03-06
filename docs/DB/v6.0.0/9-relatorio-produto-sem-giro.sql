INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','9-relatorio-produto-sem-giro.sql');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
  VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0, (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios'), 'Produtos', 5, '#', '_self', 'S');

UPDATE MENU_ITEM SET NUM_PESO = 1 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Armazenagem';
UPDATE MENU_ITEM SET NUM_PESO = 2 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Dados para exportação';
UPDATE MENU_ITEM SET NUM_PESO = 3 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Planilhas';
UPDATE MENU_ITEM SET NUM_PESO = 4 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Produtividade';
UPDATE MENU_ITEM SET NUM_PESO = 6 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Recebimento';
UPDATE MENU_ITEM SET NUM_PESO = 7 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Ressuprimento';
UPDATE MENU_ITEM SET NUM_PESO = 8 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Saída de Produtos';
UPDATE MENU_ITEM SET NUM_PESO = 9 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Produtos da Expedicao';
UPDATE MENU_ITEM SET NUM_PESO = 10 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Importar XML';
UPDATE MENU_ITEM SET NUM_PESO = 11 WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios') AND DSC_MENU_ITEM LIKE 'Relatório Geral';

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
  VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0, (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_MENU_ITEM IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM IN ('Produtos') AND COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios'))),
  'Produtos sem Giro de estoque', 1, '#', '_self', 'S');

INSERT INTO RECURSO (DSC_RECURSO, COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES ('Relatório de Produtos', SQ_RECURSO_01.NEXTVAL, 0, 'relatorio_giro-estoque');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'relatorio_giro-estoque'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Produtos sem giro de estoque');

UPDATE MENU_ITEM SET COD_RECURSO_ACAO = (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'relatorio_giro-estoque') AND COD_ACAO IN (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')) WHERE COD_MENU_ITEM = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Produtos' AND COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios')));
