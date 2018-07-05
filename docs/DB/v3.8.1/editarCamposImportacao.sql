/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'editarCamposImportacao.sql');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (
  SQ_ACAO_01.NEXTVAL, 'Editar campo da importação', 'editar-campo-importacao');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'editar-campo-importacao'), 'Editar campo da importação');

