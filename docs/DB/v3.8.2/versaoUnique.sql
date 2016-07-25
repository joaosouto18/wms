/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.2', 'versaoUnique.sql');

/* Conteúdo do arquivo */
CREATE UNIQUE INDEX VERSAO_SCRIPT ON VERSAO (SCRIPT);
