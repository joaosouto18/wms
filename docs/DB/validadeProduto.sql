CREATE TABLE MODELO_RECEBIMENTO
  (
    COD_MODELO_RECEBIMENTO NUMBER (8) NOT NULL ,
    CONTROLE_VALIDADE      CHAR (1) NOT NULL
  );

CREATE SEQUENCE SQ_MODELO_RECEBIMENTO_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE;

ALTER TABLE MODELO_RECEBIMENTO
  ADD CONSTRAINT MODELO_RECEBIMENTO_PK PRIMARY KEY (COD_MODELO_RECEBIMENTO);

ALTER TABLE PRODUTO ADD(
   DIAS_VIDA_UTIL NUMBER(8,0),
   POSSUI_VALIDADE CHAR (1) DEFAULT 'N'
);

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'MODELO RECEBIMENTO', 'modelo-recebimento');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'modelo-recebimento'), 'parametros para recebimento');

ALTER TABLE MODELO_RECEBIMENTO
  ADD (DESCRICAO VARCHAR2 (100 BYTE)) ;

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'add'), 'adicionar parametros para recebimento');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'DELETAR MODELO RECEBIMENTO', 'delete-modelo');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'delete-modelo'), 'adicionar parametros para recebimento');

  INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'edit'), 'adicionar parametros para recebimento');

ALTER TABLE RECEBIMENTO_CONFERENCIA ADD(
   DTH_VALIDADE DATE
);

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'recebimento') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'modelo-recebimento')),
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'),
'Modelo de Recebimento',
10, '#', '_self', 'S');

ALTER TABLE RECEBIMENTO_VOLUME ADD  (
    DTH_VALIDADE DATE
);

ALTER TABLE RECEBIMENTO_EMBALAGEM ADD (
  DTH_VALIDADE DATE
);