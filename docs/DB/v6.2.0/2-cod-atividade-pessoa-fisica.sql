INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.2.0','2-cod-atividade-pessoa-fisica.sql');
ALTER TABLE PESSOA_FISICA
  ADD COD_ATIVIDADE NUMBER(11) DEFAULT 0 NULL;