/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','5 funcionalidades.sql');

CREATE TABLE FUNCIONALIDADES
  (
    COD_FUNCIONALIDADE NUMBER(8,0) NOT NULL,
    DSC_FUNCIONALIDADE VARCHAR2(4000 BYTE) NOT NULL ,
    SCRIPT             VARCHAR2(200 BYTE) ,
    DTH_ATUALIZACAO    DATE,
    PRIMARY KEY (COD_FUNCIONALIDADE)
  );
