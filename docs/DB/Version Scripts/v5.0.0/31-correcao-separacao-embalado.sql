INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','31-correcao-separacao-embalado.sql');

ALTER TABLE MODELO_SEPARACAO
  ADD (TIPO_SEPARACAO_FRAC_EMB CHAR(1));

ALTER TABLE MODELO_SEPARACAO
  ADD (TIPO_SEPARACAO_NAO_FRAC_EMB CHAR(1));

UPDATE MODELO_SEPARACAO SET TIPO_SEPARACAO_NAO_FRAC_EMB = TIPO_SEPARACAO_NAO_FRACIONADO;
UPDATE MODELO_SEPARACAO SET TIPO_SEPARACAO_FRAC_EMB = TIPO_SEPARACAO_FRACIONADO;