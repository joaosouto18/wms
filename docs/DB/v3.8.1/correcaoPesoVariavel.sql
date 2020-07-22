/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'correcaoPesoVariavel.sql');

ALTER TABLE PRODUTO
  ADD (IND_POSSUI_PESO_VARIAVEL VARCHAR(1) DEFAULT 'N');

