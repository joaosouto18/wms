INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','integracaoEstoque.sql');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (601, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'INTEGRACAO'),'ESTOQUE', 'P');

CREATE TABLE ESTOQUE_ERP
  (
    COD_ESTOQUE_ERP     NUMBER(8,0) NOT NULL,
    COD_PRODUTO         VARCHAR2(20 BYTE),
    DSC_GRADE           VARCHAR2(10 BYTE),
    ESTOQUE_GERENCIAL   NUMBER(13,3),
    ESTOQUE_DISPONIVEL  NUMBER(13,3),
    VLR_ESTOQUE_TOTAL   NUMBER(13,3),
    VLR_ESTOQUE_UNIT    NUMBER(13,3),
    DSC_UNIDADE_VENDA   VARCHAR2(100 BYTE),
    FATOR_UNIDADE_VENDA NUMBER(13,3),
    PRIMARY KEY (COD_ESTOQUE_ERP)
  );

ALTER TABLE ESTOQUE_ERP
ADD CONSTRAINT ESTOQUE_ERP_FK_01 FOREIGN KEY (COD_PRODUTO, DSC_GRADE)
    REFERENCES PRODUTO (COD_PRODUTO, DSC_GRADE) ENABLE;

CREATE SEQUENCE SQ_ESTOQUE_ERP_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO, DSC_RECURSO)
VALUES (
  SQ_RECURSO_01.NEXTVAL,
  0,
  'inventario:comparativo',
  'Comparativo de Estoque'
);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO,COD_ACAO,DSC_RECURSO_ACAO)
VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL,
  (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'inventario:comparativo'),
  (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index'),
  'Comparativo Estoque ERP X WMS'
);

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (
  SQ_MENU_ITEM_01.NEXTVAL,
  (SELECT COD_RECURSO_ACAO
   FROM RECURSO_ACAO
   WHERE COD_RECURSO = (SELECT COD_RECURSO FROM recurso WHERE NOM_RECURSO LIKE 'inventario:comparativo')
         AND COD_ACAO = (SELECT COD_ACAO FROM acao WHERE NOM_ACAO LIKE 'index')),
  (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Inventário'),
  'Comparativo Estoque ERP x WMS', 5, '#', '_self', 'S'
);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO,COD_ACAO,DSC_RECURSO_ACAO)
VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL,
  (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'inventario:comparativo'),
  (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'saldo'),
  'Atualiza Saldo Estoque ERP'
);

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'PARAMETROS DE WEBSERVICE'), 'COD_ACAO_INTEGRACAO_ESTOQUE', 'Código da Ação de Integração de Estoque', 'S', 'A', '1');

UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO' WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DE WEBSERVICE';

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','integracaoEstoque.sql');