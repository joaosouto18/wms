ALTER TABLE NOTA_FISCAL DROP CONSTRAINT FK_NOFIS_VEICU;

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'remocaoRelacaoNotaVeiculo.sql');