INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','30-apontamento-tabela-mapa-quebra.sql');

ALTER TABLE MAPA_SEPARACAO_QUEBRA
MODIFY (IND_TIPO_QUEBRA VARCHAR(2 BYTE));