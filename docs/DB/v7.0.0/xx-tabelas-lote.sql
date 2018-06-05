INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', 'xx-tabelas-lote.sql');

ALTER TABLE PRODUTO ADD (IND_CONTROLA_LOTE CHAR(1) DEFAULT 'N' NOT NULL);

CREATE TABLE LOTE
  (
    COD_LOTE NUMBER(11) NOT NULL,
    DSC_LOTE VARCHAR2 (200),
    DTH_CRIACAO DATE NOT NULL,
    COD_PESSOA_CRIACAO NUMBER(11) NOT NULL,
    COD_PRODUTO NUMBER(11) NULL,
    DSC_GRADE VARCHAR2 (200),
    IND_ORIGEM_LOTE CHAR (1) NULL
  );

CREATE SEQUENCE SQ_LOTE_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

-------------------------------------------------------------------------------

CREATE TABLE PEDIDO_PRODUTO_LOTE
  (
    COD_PEDIDO_PRODUTO_LOTE NUMBER(11) NOT NULL,
    COD_PEDIDO_PRODUTO NUMBER(11) NOT NULL,
    COD_LOTE NUMBER(11) NOT NULL,
    QUANTIDADE NUMBER(13,3) NULL,
    QTD_ATENDIDA NUMBER(13,3) NULL,
    QTD_CORTE NUMBER(13,3)
  );

CREATE SEQUENCE SQ_PEDIDO_PRODUTO_LOTE_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

-------------------------------------------------------------------------------

CREATE TABLE NOTA_FISCAL_ITEM_LOTE
  (
    COD_NOTA_FISCAL_ITEM_LOTE NUMBER(11) NOT NULL,
    COD_NOTA_FISCAL_ITEM NUMBER(11) NOT NULL,
    COD_LOTE NUMBER(11) NOT NULL,
    QUANTIDADE NUMBER(13,3) NULL
  );

CREATE SEQUENCE SQ_NOTA_FISCAL_ITEM_LOTE_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

-------------------------------------------------------------------------------

ALTER TABLE RECEBIMENTO_VOLUME ADD COD_LOTE NUMBER(11) NULL;
ALTER TABLE LOTE ADD CONSTRAINT  PK_LOTE PRIMARY KEY (COD_LOTE);
ALTER TABLE RECEBIMENTO_VOLUME ADD CONSTRAINT  FK_REVOL_LOTE FOREIGN KEY (COD_LOTE) REFERENCES LOTE (COD_LOTE);

ALTER TABLE RECEBIMENTO_EMBALAGEM ADD COD_LOTE NUMBER(11) NULL;
ALTER TABLE RECEBIMENTO_EMBALAGEM ADD CONSTRAINT  FK_REEMB_LOTE FOREIGN KEY (COD_LOTE) REFERENCES LOTE (COD_LOTE);

ALTER TABLE RECEBIMENTO_CONFERENCIA ADD COD_LOTE NUMBER(11) NULL;
ALTER TABLE RECEBIMENTO_CONFERENCIA ADD CONSTRAINT  FK_CONRE_LOTE FOREIGN KEY (COD_LOTE) REFERENCES LOTE (COD_LOTE);

-------------------------------------------------------------------------------