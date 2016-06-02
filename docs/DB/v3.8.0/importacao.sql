INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação', null, 'importacao');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (
  SQ_RECURSO_01.NEXTVAL, 'Importação de Arquivos TXT e CSV', (
  select COD_RECURSO from recurso where NOM_RECURSO like 'importacao'), 'importacao:index');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'index'), 'Index Importação');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (
    SQ_ACAO_01.NEXTVAL, 'Alterar Status do Arquivo de Importação', 'alterar-status');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (
  SQ_ACAO_01.NEXTVAL, 'Lista de campos', 'lista-campos-importacao');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (
  SQ_ACAO_01.NEXTVAL, 'Configurar Importação', 'configuracao-importacao');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'index'), 'Index Importação');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'alterar-status'), 'Alterar Status do Arquivo de Importação');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'configuracao-importacao'), 'Configurar Importação');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (
  SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index'),
  (select COD_ACAO from acao where NOM_ACAO like 'lista-campos-importacao'), 'Lista de campos');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (
 SQ_MENU_ITEM_01.NEXTVAL, 0,0,'Importação',10,'#','_self','S');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (
  SQ_MENU_ITEM_01.NEXTVAL,
  (SELECT COD_RECURSO_ACAO
     FROM RECURSO_ACAO
    where COD_RECURSO = (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index')
      and cod_acao = (select COD_ACAO from acao where NOM_ACAO like 'index')),
  (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Importação'),
  'Importar Arquivos', 1, '#', '_self', 'S');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (
  SQ_MENU_ITEM_01.NEXTVAL,
  (SELECT COD_RECURSO_ACAO
     FROM RECURSO_ACAO
    where COD_RECURSO = (select COD_RECURSO from recurso where NOM_RECURSO like 'importacao:index')
      and cod_acao = (select COD_ACAO from acao where NOM_ACAO like 'configuracao-importacao')),
  (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Importação'),
  'Configurações de importação', 2, '#', '_self', 'S');

UPDATE MENU_ITEM SET NUM_PESO = 11 WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Relatórios';

ALTER TABLE PARAMETRO MODIFY (DSC_PARAMETRO VARCHAR2(60));

  CREATE TABLE IMPORTACAO_ARQUIVO
(
  COD_IMPORTACAO_ARQUIVO NUMBER(8) NOT NULL
, TABELA_DESTINO VARCHAR2(20)
, NOME_ARQUIVO VARCHAR2(120)
, CARACTER_QUEBRA VARCHAR2(20)
, CABECALHO CHAR(1),
, SEQUENCIA NUMBER(8)
, IND_ATIVO CHAR(1) DEFAULT 'S'
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
, VALOR_PADRAO VARCHAR2(120)
, PREENCH_OBRIGATORIO CHAR(1)
, CONSTRAINT IMPORTACAO_CAMPOS_PK PRIMARY KEY
  (
    COD_IMPORTACAO_CAMPOS
  )
  ENABLE
);

alter table "IMPORTACAO_CAMPOS" add constraint IMP_ARQ_CAMPOS foreign key("COD_IMPORTACAO_ARQUIVO") references "IMPORTACAO_ARQUIVO"("COD_IMPORTACAO_ARQUIVO");

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL,
(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),
'DIRETORIO_IMPORTACAO',
'Diretório dos Arquivos de Importação',
'N',
'A',
'');

CREATE SEQUENCE SQ_IMPORTACAO_CAMPOS_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;