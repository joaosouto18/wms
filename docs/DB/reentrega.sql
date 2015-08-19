CREATE TABLE NOTA_FISCAL_SAIDA (
   COD_NOTA_FISCAL_SAIDA NUMBER (8) NOT NULL,
   NUMERO_NOTA NUMBER (8) NOT NULL,
   SERIE VARCHAR2 (20 BYTE) NOT NULL,
   VALOR_TOTAL_NF NUMBER(15,2) NOT NULL
);
ALTER TABLE NOTA_FISCAL_SAIDA ADD PRIMARY KEY (COD_NOTA_FISCAL_SAIDA);

CREATE SEQUENCE SQ_NF_SAIDA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE NOTA_FISCAL_SAIDA_PRODUTO (
   COD_NOTA_FISCAL_SAIDA_PRODUTO NUMBER (8) NOT NULL,
   COD_PRODUTO VARCHAR2 (20 BYTE) NOT NULL,
   DSC_GRADE VARCHAR2 (20 BYTE) NOT NULL,
   QUANTIDADE NUMBER (8) NOT NULL,
   VALOR_VENDA NUMBER(15,2) NOT NULL,
   COD_NOTA_FISCAL_SAIDA NUMBER(8) NOT NULL
);

CREATE SEQUENCE SQ_NF_SAIDA_PRODUTO_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE NOTA_FISCAL_SAIDA_PEDIDO (
   COD_NOTA_FISCAL_SAIDA_PEDIDO NUMBER (8) NOT NULL,
   COD_NOTA_FISCAL_SAIDA NUMBER (8) NOT NULL,
   COD_PEDIDO NUMBER (8) NOT NULL
);

CREATE SEQUENCE SQ_NF_SAIDA_PEDIDO_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL, 23, 'CONFERE_RECEBIMENTO_REENTREGA', 'Confere Recebimento de Reentrega (S/N)', 'N', 'A', 'S');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL, 23, 'CONFERE_EXPEDICAO_REENTREGA', 'Confere Expedi��o de Reentrega (S/N)', 'N', 'A', 'S');

ALTER TABLE NOTA_FISCAL_SAIDA
   ADD (COD_STATUS NUMBER (8));

CREATE TABLE RECEBIMENTO_REENTREGA (
   COD_RECEBIMENTO_REENTREGA NUMBER (8) NOT NULL,
   COD_STATUS NUMBER (8) NOT NULL,
   DTH_CRIACAO DATE NOT NULL,
   OBSERVACAO VARCHAR2 (100 BYTE),
   COD_USUARIO NUMBER (8) NOT NULL
);

CREATE SEQUENCE SQ_RECEBIMENTO_REENTREGA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE RECEBIMENTO_REENTREGA_NF (
   COD_RECEBIMENTO_REENTREGA_NF NUMBER (8) NOT NULL,
   COD_RECEBIMENTO_REENTREGA NUMBER(8) NOT NULL,
   COD_NOTA_FISCAL NUMBER (8) NOT NULL
);

CREATE SEQUENCE SQ_RECEBIMENTO_REENTREGA_NF_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE CONF_RECEB_REENTREGA
  (
    COD_CONF_RECEB_REENTREGA NUMBER (8) NOT NULL,
    COD_RECEBIMENTO_REENTREGA NUMBER (8) NOT NULL,
    COD_PRODUTO_VOLUME NUMBER(8),
    COD_PRODUTO_EMBALAGEM NUMBER(8),
    COD_PRODUTO VARCHAR2 (20 BYTE) NOT NULL,
    DSC_GRADE VARCHAR2 (20 BYTE) NOT NULL,
    QTD_CONFERIDA NUMBER (8) NOT NULL,
    QTD_EMBALAGEM_CONFERIDA NUMBER (8) NOT NULL,
    COD_OS NUMBER (8) NOT NULL,
    NUM_CONFERENCIA NUMBER(8) NOT NULL,
    DTH_CONFERENCIA DATE NOT NULL
  );

CREATE SEQUENCE SQ_CONF_RECEB_REENTREGA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA, DSC_TIPO_SIGLA, IND_SIGLA_SISTEMA) VALUES (SQ_TIPO_SIGLA_01.NEXTVAL, 'REENTREGA', 'N');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (553, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'REENTREGA'), 'NOTA FISCAL EMITIDA', 'F');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (554, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'REENTREGA'), 'DEVOLVIDO PARA REENTREGA', 'D');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (555, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'REENTREGA'), 'EXPEDIDO REENTREGA', 'E');

CREATE TABLE REENTREGA (
   COD_REENTREGA NUMBER (8) PRIMARY KEY,
   COD_CARGA NUMBER (8) NOT NULL,
   COD_NOTA_FISCAL_SAIDA NUMBER (8) NOT NULL,
   DTH_REENTREGA DATE NOT NULL,
   IND_ETIQUETA_MAPA_GERADO  VARCHAR2(20 BYTE) default 'N' NOT NULL
);

ALTER TABLE REENTREGA
  ADD CONSTRAINT REENTREGA_CARGA_FK FOREIGN KEY (COD_CARGA) REFERENCES CARGA (COD_CARGA) NOT DEFERRABLE ;

ALTER TABLE REENTREGA
  ADD CONSTRAINT REENTREGA_NF_SAIDA_FK FOREIGN KEY (COD_NOTA_FISCAL_SAIDA) REFERENCES NOTA_FISCAL_SAIDA (COD_NOTA_FISCAL_SAIDA) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_REENTREGA_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

ALTER TABLE RECEBIMENTO_REENTREGA
  ADD CONSTRAINT RECEBIMENTO_REENTREGA_SIGLA_FK FOREIGN KEY (COD_STATUS) REFERENCES SIGLA (COD_SIGLA) NOT DEFERRABLE ;

  ALTER TABLE RECEBIMENTO_REENTREGA
  ADD CONSTRAINT RECEB_REENTREGA_USUARIO_FK FOREIGN KEY (COD_USUARIO) REFERENCES USUARIO (COD_USUARIO) NOT DEFERRABLE ;

INSERT INTO ATIVIDADE (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES (15, 'Reentrega Mercadorias', 1);

ALTER TABLE ORDEM_SERVICO
   ADD (COD_RECEBIMENTO_REENTREGA NUMBER (8));

INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA, DSC_TIPO_SIGLA, IND_SIGLA_SISTEMA) VALUES (SQ_TIPO_SIGLA_01.NEXTVAL, 'REENTREGA','S');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (556, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'REENTREGA'), 'FINALIZADO', 'E');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (557, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'REENTREGA'), 'RECEBIDA', 'E');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (558, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS ETIQUETA SEPARACAO'), 'PENDENTE DE CONFERENCIA de REENTREGA', 'E');

ALTER TABLE RECEBIMENTO_REENTREGA
ADD (NUM_CONFERENCIA NUMBER(8));

CREATE TABLE ANDAMENTO_NOTA_FISCAL (
   COD_ANDAMENTO_NOTA_FISCAL NUMBER (8) PRIMARY KEY,
   COD_NOTA_FISCAL_SAIDA NUMBER (8) NOT NULL,
   COD_EXPEDICAO NUMBER (8) NOT NULL,
   COD_USUARIO NUMBER(8) NOT NULL,
   COD_STATUS  NUMBER(8) NOT NULL,
   DATA DATE NOT NULL,
   OBSERVACAO VARCHAR2 (100 BYTE)
);

CREATE SEQUENCE SQ_ANDAMENTO_NOTA_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE;

ALTER TABLE ANDAMENTO_NOTA_FISCAL
  ADD CONSTRAINT ANDAMENTO_NOTA_FK FOREIGN KEY (COD_NOTA_FISCAL_SAIDA) REFERENCES NOTA_FISCAL_SAIDA (COD_NOTA_FISCAL_SAIDA) NOT DEFERRABLE;

ALTER TABLE ANDAMENTO_NOTA_FISCAL
  ADD CONSTRAINT ANDAMENTO_EXPEDICAO_FK FOREIGN KEY (COD_EXPEDICAO) REFERENCES EXPEDICAO (COD_EXPEDICAO) NOT DEFERRABLE;

ALTER TABLE ANDAMENTO_NOTA_FISCAL
  ADD CONSTRAINT ANDAMENTO_USUARIO_FK FOREIGN KEY (COD_USUARIO) REFERENCES USUARIO (COD_USUARIO) NOT DEFERRABLE;

ALTER TABLE ANDAMENTO_NOTA_FISCAL
  ADD CONSTRAINT ANDAMENTO_STATUS_FK FOREIGN KEY (COD_STATUS) REFERENCES SIGLA (COD_SIGLA) NOT DEFERRABLE;

ALTER TABLE PEDIDO_PRODUTO ADD(
   VALOR_VENDA  NUMBER(15,3) DEFAULT 0
);
