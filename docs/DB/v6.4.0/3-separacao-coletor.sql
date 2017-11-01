CREATE TABLE SEPARACAO_MAPA_SEPARACAO
(
    COD_SEPARACAO_MAPA_SEPARACAO NUMBER (8) NOT NULL,
    COD_OS NUMBER (8) NOT NULL,
    COD_MAPA_SEPARACAO NUMBER (8) NOT NULL,
    COD_PRODUTO VARCHAR2 (10 BYTE)  NOT NULL,
    DSC_GRADE VARCHAR2 (10 BYTE)  NOT NULL,
    COD_PRODUTO_VOLUME NUMBER (8),
    COD_PRODUTO_EMBALAGEM NUMBER (8),
    QTD_EMBALAGEM NUMBER (13),
    QTD_SEPARADA NUMBER (8) NOT NULL,
    DTH_SEPARACAO DATE
);

CREATE SEQUENCE SQ_SEPARACAO_MAPA_SEPARACAO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD  (IND_SEPARADO CHAR(2));

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (539, 72, 'MAPA SEPARADO', 'MS');