/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.2', 'apontamentoSeparacao.sql');

INSERT INTO ACAO (COD_ACAO,DSC_ACAO,NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Conferente de separação de etiquetas', 'conferente-apontamento-separacao');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL,(SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index'),
  (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'conferente-apontamento-separacao'), 'Conferente apontamento etiquetas');

