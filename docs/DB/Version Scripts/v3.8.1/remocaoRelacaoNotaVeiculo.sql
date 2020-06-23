/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'remocaoRelacaoNotaVeiculo.sql');

ALTER TABLE NOTA_FISCAL DROP CONSTRAINT FK_NOFIS_VEICU;

