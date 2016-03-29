INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação', null, 'importacao');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação de Arquivos TXT e CSV', (
  select COD_RECURSO from recurso where NOM_RECURSO like 'importacao'), 'importacao:index');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'index'), 'Index Importação');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (
  SQ_MENU_ITEM_01.NEXTVAL, (
  SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where COD_RECURSO = (
    select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index') and cod_acao = (
    select COD_ACAO from acao where NOM_ACAO like 'index')
  ),  0, 'Importação', 1, '#', '_self', 'S');


  CREATE TABLE IMPORTACAO_ARQUIVO
(
  COD_IMPORTACAO_ARQUIVO NUMBER(8) NOT NULL
, TABELA_DESTINO VARCHAR2(20)
, NOME_ARQUIVO VARCHAR2(120)
, CARACTER_QUEBRA VARCHAR2(20)
, CABECALHO CHAR(1)
, CONSTRAINT ARQUIVO_IMPORTACAO_PK PRIMARY KEY
  (
    COD_IMPORTACAO_ARQUIVO
  )
  ENABLE
);

CREATE SEQUENCE SQ_IMPORTACAO_ARQUIVO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE IMPORTACAO_CAMPOS
(
  COD_IMPORTACAO_CAMPOS NUMBER(8) NOT NULL
, COD_IMPORTACAO_ARQUIVO NUMBER(8) NOT NULL
, NOME_CAMPO VARCHAR2(20)
, POSICAO_TXT VARCHAR2(20)
, TAMANHO_INICIO VARCHAR2(20)
, TAMANHO_FIM VARCHAR2(20)
, VALOR_PADRAO VARCHAR2(120),
, CONSTRAINT IMPORTACAO_CAMPOS_PK PRIMARY KEY
  (
    COD_IMPORTACAO_CAMPOS
  )
  ENABLE
);

alter table "IMPORTACAO_CAMPOS" add constraint IMP_ARQ_CAMPOS foreign key("COD_IMPORTACAO_ARQUIVO") references "IMPORTACAO_ARQUIVO"("COD_IMPORTACAO_ARQUIVO")



CREATE SEQUENCE SQ_IMPORTACAO_CAMPOS_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;