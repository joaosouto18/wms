/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'volumePatrimonioSequenciado.sql');

ALTER TABLE EXPEDICAO_VOLUME_PATRIMONIO ADD NUM_SEQUENCIA NUMBER(8,0);

