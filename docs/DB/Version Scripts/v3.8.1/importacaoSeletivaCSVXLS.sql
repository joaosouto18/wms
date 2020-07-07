/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'importacaoSeletivaCSVXLS.sql');

ALTER TABLE IMPORTACAO_ARQUIVO ADD NOM_INPUT VARCHAR(20);

