INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','10-qtd-funcionario-apontamento-produtividade.sql');

ALTER TABLE EQUIPE_SEPARACAO
  ADD NUM_FUNCIONARIO NUMBER(15,3) DEFAULT 0 NULL;

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Apagar Apontamento Produtividade', 'apaga-apontamento-separacao');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index'),
  (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'apaga-apontamento-separacao'), 'Apagar Apontamento Produtividade');