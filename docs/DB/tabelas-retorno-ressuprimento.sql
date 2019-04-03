INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', '04-tabelas-lote.sql');

INSERT INTO ATIVIDADE (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES (17, 'RETORNO DE RESSUPRIMENTO', 1);

CREATE TABLE RETORNO_RESSUPRIMENTO
  (
    COD_RETORNO_RESSUPRIMENTO NUMBER(8,0) NOT NULL,
    COD_ONDA_RESSUPRIMENTO_OS NUMBER(8,0) NOT NULL,
    COD_OS NUMBER(8,0) NOT NULL,
    COD_DEPOSITO_ENDERECO NUMBER(8,0) NOT NULL,
    DTH_MOVIMENTACAO DATE NOT NULL
  );

CREATE SEQUENCE SQ_RETORNO_RESSUPRIMENTO_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE RETORNO_RESSUPRIMENTO ADD CONSTRAINT  PK_RETORNO_RESSUPRIMENTO PRIMARY KEY (COD_RETORNO_RESSUPRIMENTO);

ALTER TABLE RETORNO_RESSUPRIMENTO
  ADD CONSTRAINT FK_ONDA_RESSUPRIMENTO_OS FOREIGN KEY ( COD_ONDA_RESSUPRIMENTO_OS )
    REFERENCES ONDA_RESSUPRIMENTO_OS ( COD_ONDA_RESSUPRIMENTO_OS )
      NOT DEFERRABLE;

ALTER TABLE RETORNO_RESSUPRIMENTO
  ADD CONSTRAINT FK_COD_OS FOREIGN KEY ( COD_OS )
    REFERENCES ORDEM_SERVICO ( COD_OS )
      NOT DEFERRABLE;

ALTER TABLE RETORNO_RESSUPRIMENTO
  ADD CONSTRAINT FK_COD_DEPOSITO_ENDERECO FOREIGN KEY ( COD_DEPOSITO_ENDERECO )
    REFERENCES DEPOSITO_ENDERECO ( COD_DEPOSITO_ENDERECO )
      NOT DEFERRABLE;
-------------------------------------------------------------------------------

CREATE TABLE RETORNO_RESSUPRIMENTO_PRODUTO
  (
    COD_RETORNO_RESSUP_PROD NUMBER(8,0) NOT NULL,
    COD_RETORNO_RESSUPRIMENTO NUMBER(8,0) NOT NULL,
    COD_PRODUTO VARCHAR(20) NOT NULL,
    DSC_GRADE VARCHAR2 (200) NOT NULL,
    COD_PRODUTO_EMBALAGEM NUMBER(8,0) NOT NULL,
    COD_PRODUTO_VOLUME NUMBER(8,0) NOT NULL,
    QTD NUMBER(11,2) NOT NULL,
    DSC_LOTE VARCHAR2 (200) NOT NULL
  );

CREATE SEQUENCE SQ_RETORNO_RESSUP_PROD_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE RETORNO_RESSUPRIMENTO_PRODUTO ADD CONSTRAINT  PK_RETORNO_RESSUP_PROD PRIMARY KEY (COD_RETORNO_RESSUP_PROD);

ALTER TABLE RETORNO_RESSUPRIMENTO_PRODUTO
  ADD CONSTRAINT FK_RETORNO_RESSUPRIMENTO_OS FOREIGN KEY ( COD_RETORNO_RESSUPRIMENTO )
    REFERENCES RETORNO_RESSUPRIMENTO ( COD_RETORNO_RESSUPRIMENTO )
      NOT DEFERRABLE;

-------------------------------------------------------------------------------
