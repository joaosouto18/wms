INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação', null, 'importacao');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação de Arquivos TXT e CSV', (
  select COD_RECURSO from recurso where NOM_RECURSO like 'importacao'), 'importacao:index');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'index'), 'Index Importação');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (
  SQ_MENU_ITEM_01.NEXTVAL, (
  SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where COD_RECURSO = (
    select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index') and cod_acao = (
    select COD_ACAO from acao where NOM_ACAO like 'index')
  ),  0, 'Importação', 1, '#', '_self', 'S');