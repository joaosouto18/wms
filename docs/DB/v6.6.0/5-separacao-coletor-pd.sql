INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.6.0','4-separacao-coletor-pd.sql');


ALTER TABLE ETIQUETA_SEPARACAO ADD (COD_USUARIO_SEPARACAO NUMBER(8,0));
ALTER TABLE ETIQUETA_SEPARACAO ADD (DTH_SEPARACAO DATE);