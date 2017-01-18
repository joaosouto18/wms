CREATE TABLE INVENTARIO_ENDERECO_PRODUTO
  (
    COD_INVENTARIO_END_PRODUTO NUMBER(8,0) NOT NULL,
    COD_INVENTARIO_ENDERECO NUMBER(8,0) NOT NULL,
    COD_PRODUTO   VARCHAR2(20 BYTE) NOT NULL ,
    DSC_GRADE     VARCHAR2(10 BYTE) NOT NULL ,
    PRIMARY KEY (COD_INVENTARIO_END_PRODUTO)
  );

CREATE SEQUENCE SQ_INVENTARIO_END_PROD_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE INVENTARIO_ENDERECO_PRODUTO
ADD CONSTRAINT INVENTARIO_END_PROD_FK_01 FOREIGN KEY ( COD_INVENTARIO_ENDERECO)
    REFERENCES INVENTARIO_ENDERECO (COD_INVENTARIO_ENDERECO) ENABLE;

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','inventario-ccb.sql');

