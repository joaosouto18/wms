ALTER TABLE IMPORTACAO_ARQUIVO ADD NOM_INPUT VARCHAR(20);

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'importacaoSeletivaCSVXLS.sql');