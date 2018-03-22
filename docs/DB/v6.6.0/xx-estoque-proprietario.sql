INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.16.0','xx-estoque-proprietario.sql');

CREATE TABLE ESTOQUE_PROPRIETARIO
(
  COD_ESTOQUE_PROPRIETARIO    NUMBER (8) NOT NULL,
  COD_PRODUTO           VARCHAR2 (20 BYTE)  NOT NULL ,
  DSC_GRADE             VARCHAR2 (10 BYTE)  NOT NULL,
  COD_PESSOA            NUMBER (8) NOT NULL,
  IND_OPERCAO           CHAR(2 BYTE),
  COD_OPERACAO          NUMBER (8) NULL,
  COD_OPERACAO_DETALHE  NUMBER (8) NULL,
  QTD                   NUMBER(13,3),
  DTH_OPERACAO          DATE NOT NULL,
  SALDO_FINAL           NUMBER(13,3)
);
ALTER TABLE ESTOQUE_PROPRIETARIO ADD CONSTRAINT ESTOQUE_PROPRIETARIO_PK PRIMARY KEY ( COD_ESTOQUE_PROPRIETARIO ) ;

CREATE SEQUENCE SQ_ESTOQUE_PROPRIETARIO
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE NOTA_FISCAL ADD(
  COD_PESSOA_PROPRIETARIO NUMBER (8)
);

ALTER TABLE PEDIDO ADD(
  COD_PESSOA_PROPRIETARIO NUMBER (8)
);

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DO SISTEMA'),
'CONTROLE_PROPRIETARIO', 'Controle de Proprietario','N','A','N');


ALTER TABLE EMPRESA ADD(
  IDENTIFICACAO VARCHAR (20),
  PRIORIDADE_ESTOQUE NUMBER (8)
);

ALTER TABLE EMPRESA
ADD CONSTRAINT UQ_IDENTIFICACAO UNIQUE (IDENTIFICACAO);

ALTER TABLE EMPRESA
ADD CONSTRAINT UQ_PRIORIDADE_ESTOQUE UNIQUE (PRIORIDADE_ESTOQUE);

INSERT INTO RECURSO (DSC_RECURSO,COD_RECURSO,COD_RECURSO_PAI,NOM_RECURSO)
  VALUES ('Empresa',SQ_RECURSO_01.NEXTVAL,0,'empresa');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'empresa'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index'), 'Empresa');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'empresa'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'add'), 'Adicionar Empresa');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'empresa'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'edit'), 'Editar Empresa');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'empresa'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'delete'), 'Apagar Empresa');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO,DSC_URL,DSC_TARGET,SHOW)
  VALUES (SQ_MENU_ITEM_01.NEXTVAL, (select COD_RECURSO_ACAO from recurso_acao where COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'empresa') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index')),
  20,'Empresa',60,'#','_self','S');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Relatório de Estoque Proprietário', 0, 'enderecamento:relatorio_estoque-proprietario');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_estoque-proprietario'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Estoque Proprietário');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_estoque-proprietario') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 54 AND DSC_MENU_ITEM = 'Armazenagem'), 'Relatório de Estoque Proprietário', 10, '#', 'S');
