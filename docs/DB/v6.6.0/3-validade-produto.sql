INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.6.0','3-validade-produto.sql');


ALTER TABLE PRODUTO ADD DIAS_VIDA_UTIL_MAX NUMBER(8,0) DEFAULT 2000;