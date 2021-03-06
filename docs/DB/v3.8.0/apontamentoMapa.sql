INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.0', 'apontamentoMapa.sql');

CREATE TABLE APONTAMENTO_SEPARACAO_MAPA
  (
    COD_APONTAMENTO_MAPA NUMBER(8) NOT NULL,
    COD_USUARIO NUMBER(8) NOT NULL,
    COD_MAPA_SEPARACAO NUMBER(8) NOT NULL,
    DTH_CONFERENCIA TIMESTAMP NOT NULL  
  );

CREATE SEQUENCE SQ_APONT_MAPA_SEP_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;