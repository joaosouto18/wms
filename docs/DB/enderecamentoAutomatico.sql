INSERT INTO RECURSO (DSC_RECURSO, COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES ('Prioridade Recursos', SQ_RECURSO_01.NEXTVAL, 0, 'enderecamento:modelo') ;

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Prioridade de Estrutura de Armazenagem');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO like 'enderecamento:modelo')),
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros') AND DSC_MENU_ITEM = 'Modelo de Armazenagem'), 'Modelo de Armazenagem', 10, '#', '_self', 'S');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Prioridade de Estrutura de Armazenagem');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'), 'Prioridade de Estrutura de Armazenagem');

CREATE
  TABLE MODELO_ENDERECAMENTO
  (
    COD_MODELO_ENDERECAMENTO NUMBER (8) NOT NULL ,
    DSC_MODELO_ENDERECAMENTO VARCHAR2 (128 BYTE) ,
    COD_MODELO_REFERENCIA    NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_ENDERECAMENTO ADD CONSTRAINT MODELO_ENDERECAMENTO_PK PRIMARY
KEY ( COD_MODELO_ENDERECAMENTO ) ;

CREATE
  TABLE MODELO_END_AREA_ARMAZ
  (
    COD_MODELO_END_AREA_ARMAZ NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO  NUMBER (8) NOT NULL ,
    COD_AREA_ARMAZENAGEM      NUMBER (8) NOT NULL ,
    COD_PRIORIDADE            NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT MODELO_END_AREA_ARMAZ_PK
PRIMARY KEY ( COD_MODELO_END_AREA_ARMAZ ) ;

CREATE
  TABLE MODELO_END_EST_ARMAZ
  (
    COD_MODELO_END_EST_ARMAZ NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO NUMBER (8) NOT NULL ,
    COD_TIPO_EST_ARMAZ       NUMBER (8) NOT NULL ,
    COD_PRIORIDADE           NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT MODELO_END_EST_ARMAZ_PK PRIMARY
KEY ( COD_MODELO_END_EST_ARMAZ ) ;

CREATE
  TABLE MODELO_END_TIPO_ENDERECO
  (
    COD_MODELO_END_TIPO_ENDERECO NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO     NUMBER (8) NOT NULL ,
    COD_TIPO_ENDERECO            NUMBER (8) NOT NULL ,
    COD_PRIORIDADE               NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT MODELO_END_TIPO_ENDERECO_PK
PRIMARY KEY ( COD_MODELO_END_TIPO_ENDERECO ) ;

ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT M_END_AREA_ARMAZENAGEM_FK
FOREIGN KEY ( COD_AREA_ARMAZENAGEM ) REFERENCES WMS_DEVELOP.AREA_ARMAZENAGEM (
COD_AREA_ARMAZENAGEM ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT M_END_MODELO_ENDERECAMENTO_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT M_END_EST_ARMAZ_M_END_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT M_END_TIPO_EST_ARMAZ_FK FOREIGN
KEY ( COD_TIPO_EST_ARMAZ ) REFERENCES WMS_DEVELOP.TIPO_EST_ARMAZ (
COD_TIPO_EST_ARMAZ ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT TIPO_ENDERECO_FK FOREIGN
KEY ( COD_TIPO_ENDERECO ) REFERENCES WMS_DEVELOP.TIPO_ENDERECO (
COD_TIPO_ENDERECO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT M_END_TIPO_ENDERECO_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_MODELO_ENDERECAMENTO_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_AREA_ARMAZ_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_EST_ARMAZ_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_TIPO_ENDERECO_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Prioridade de Estrutura de Armazenagem');



CREATE TABLE PRODUTO_END_TIPO_EST_ARMAZ
  (
    COD_PRODUTO_END_TIPO_EST_ARMAZ NUMBER (8) NOT NULL ,
    COD_TIPO_EST_ARMAZ             NUMBER (8) NOT NULL ,
    COD_PRODUTO                    VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                      VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE                 NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PRODUTO_END_TIPO_EST_ARMAZ_PK PRIMARY KEY ( COD_PRODUTO_END_TIPO_EST_ARMAZ ) ;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PROD_END_TP_EST_ARMAZ_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PROD_END_TP_EST_ARMAZ_TPEST_FK FOREIGN KEY ( COD_TIPO_EST_ARMAZ ) REFERENCES TIPO_EST_ARMAZ ( COD_TIPO_EST_ARMAZ ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_TIPO_EST_ARMAZ
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE PRODUTO_END_AREA_ARMAZENAGEM
  (
    COD_PRODUTO_END_AREA_ARMAZ   NUMBER (8) NOT NULL ,
    COD_AREA_ARMAZENAGEM             NUMBER (8) NOT NULL ,
    COD_PRODUTO                  VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                    VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE               NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_PK PRIMARY KEY ( COD_PRODUTO_END_AREA_ARMAZ ) ;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_AREA_FK FOREIGN KEY ( COD_AREA_ARMAZENAGEM ) REFERENCES AREA_ARMAZENAGEM ( COD_AREA_ARMAZENAGEM ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_AREA_ARMAZENAGEM
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE PRODUTO_END_TIPO_ENDERECO
  (
    COD_PRODUTO_END_TIPO_ENDERECO   NUMBER (8) NOT NULL ,
    COD_TIPO_ENDERECO             NUMBER (8) NOT NULL ,
    COD_PRODUTO                  VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                    VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE               NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PRODUTO_END_TIPO_ENDERECO_PK PRIMARY KEY ( COD_PRODUTO_END_TIPO_ENDERECO ) ;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PROD_END_TIPO_ENDERECO_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PROD_END_TIPO_END_TPEND_FK FOREIGN KEY ( COD_TIPO_ENDERECO ) REFERENCES TIPO_ENDERECO ( COD_TIPO_ENDERECO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_TIPO_ENDERECO
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE PRODUTO
  ADD ( COD_ENDERECO_REF_END_AUTO NUMBER (8));

ALTER TABLE PRODUTO
  ADD CONSTRAINT PRODUTO_ENDERECO_REFERENCIA_FK FOREIGN KEY ( COD_ENDERECO_REF_END_AUTO ) REFERENCES DEPOSITO_ENDERECO ( COD_DEPOSITO_ENDERECO ) NOT DEFERRABLE ;
