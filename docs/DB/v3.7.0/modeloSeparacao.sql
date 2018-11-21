CREATE TABLE MODELO_SEPARACAO
  (
    COD_MODELO_SEPARACAO          NUMBER (8) NOT NULL ,
    DSC_MODELO_SEPARACAO          CHAR (40) ,
    TIPO_SEPARACAO_FRACIONADO     CHAR (1) ,
    TIPO_SEPARACAO_NAO_FRACIONADO CHAR (1) ,
    UTILIZA_QUEBRA_COLETOR        CHAR (1) ,
    UTILIZA_ETIQUETA_MAE          CHAR (1) ,
    UTILIZA_CAIXA_MASTER          CHAR (1) ,
    QUEBRA_PULMA_DOCA             CHAR (1) ,
    TIPO_QUEBRA_VOLUME            CHAR (1) ,
    TIPO_DEFAUL_EMBALADO          CHAR (1) ,
    TIPO_CONFERENCIA_EMBALADO     CHAR (1) ,
    TIPO_CONFERENCIA_NAO_EMBALADO CHAR (1)
  ) ;
ALTER TABLE MODELO_SEPARACAO ADD CONSTRAINT MODELO_SEPARACAO_PK PRIMARY KEY ( COD_MODELO_SEPARACAO ) ;

CREATE TABLE MODELO_SEPARACAO_TPQUEB_FRAC
  (
    COD_MODELO_SEPARACAO NUMBER (5) NOT NULL ,
    IND_TIPO_QUEBRA      CHAR (1)
  ) ;
ALTER TABLE MODELO_SEPARACAO_TPQUEB_FRAC ADD CONSTRAINT MOD_SEP_TPQUEB_FRAC_FK FOREIGN KEY ( COD_MODELO_SEPARACAO ) REFERENCES MODELO_SEPARACAO ( COD_MODELO_SEPARACAO ) ON
DELETE CASCADE ;

CREATE TABLE MODELO_SEPARACAO_TPQUEB_NFRAC
  (
    COD_MODELO_SEPARACAO NUMBER (5) NOT NULL ,
    IND_TIPO_QUEBRA      CHAR (1)
  ) ;
ALTER TABLE MODELO_SEPARACAO_TPQUEB_NFRAC ADD CONSTRAINT MOD_SEP_TPQUEB_NFRAC_FK FOREIGN KEY ( COD_MODELO_SEPARACAO ) REFERENCES MODELO_SEPARACAO ( COD_MODELO_SEPARACAO ) ON
DELETE CASCADE ;

CREATE SEQUENCE SQ_MODELO_SEPARACAO_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO recurso (dsc_recurso, cod_recurso, cod_recurso_pai, nom_recurso) VALUES ('Modelo de Separacao', SQ_RECURSO_01.NEXTVAL, 0, 'expedicao:modelo-separacao');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:modelo-separacao'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Adicionar');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:modelo-separacao'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'), 'Editar');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:modelo-separacao'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Deletar');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:modelo-separacao'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:modelo-separacao') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'),
'Modelo de Separação',
10, '#', '_self', 'S');

ALTER TABLE MODELO_SEPARACAO_TPQUEB_FRAC ADD CONSTRAINT MOD_SEPARACAO_TPQUEB_FRAC_PK PRIMARY KEY ( COD_MODELO_SEPARACAO, IND_TIPO_QUEBRA ) ;
ALTER TABLE MODELO_SEPARACAO_TPQUEB_NFRAC ADD CONSTRAINT MOD_SEPARACAO_TPQUEB_NFRAC_PK PRIMARY KEY ( COD_MODELO_SEPARACAO, IND_TIPO_QUEBRA ) ;

CREATE SEQUENCE  SQ_ROTA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE ROTA (
  COD_ROTA NUMBER(8) PRIMARY KEY,
  NOME_ROTA VARCHAR2(50)
);

CREATE SEQUENCE  SQ_ROTA_PRACA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE TABLE ROTA_PRACA (
  COD_ROTA_PRACA NUMBER(8) PRIMARY KEY,
  COD_ROTA NUMBER(8) ,
  COD_PRACA NUMBER(8)
);

CREATE SEQUENCE  SQ_PRACA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE PRACA (
  COD_PRACA NUMBER(8) PRIMARY KEY,
  NOME_PRACA VARCHAR2(50)
);

CREATE SEQUENCE  SQ_PRACA_FAIXA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE PRACA_FAIXA (
  COD_PRACA_FAIXA NUMBER(8) PRIMARY KEY,
  COD_PRACA NUMBER(8) ,
  FAIXA_CEP1 VARCHAR2(50),
  FAIXA_CEP2 VARCHAR2(50)
);

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Praça', 0, 'praca');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Cadastrar Praça');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'), 'Alterar Praça');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Grid Praça');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Excluir Praça');;
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca') and COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Controle de Acesso' AND COD_PAI = 13), 'Cadastrar Praça', 1, '#', 'S');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Pegar Praças', 'getPracasAjax');
INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Rota', 0, 'rota');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Cadastrar Rota');INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Pegar Pracas', 'getPracasAjax');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'getPracasAjax'), 'Pegar Praças');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Grid Rota');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Excluir Rota');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota') and COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Controle de Acesso' AND COD_PAI = 13), 'Cadastrar Rota', 1, '#', 'S');

--CORREÇÃO DO MENU DE CADASTRO DE PRAÇA, ROTA E MODELO DE SEPARAÇÃO--

UPDATE MENU_ITEM
SET COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Cadastros') AND DSC_MENU_ITEM = 'Cliente')
WHERE COD_RECURSO_ACAO =
(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'rota') and COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'));

UPDATE MENU_ITEM
SET COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Cadastros') AND DSC_MENU_ITEM = 'Cliente')
WHERE COD_RECURSO_ACAO =
(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'praca') and COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'));

DELETE FROM MENU_ITEM WHERE COD_RECURSO_ACAO IN (
SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (
SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'modelo-separacao'));

DELETE FROM RECURSO_ACAO WHERE COD_RECURSO IN (
SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'modelo-separacao');

DELETE FROM RECURSO WHERE NOM_RECURSO = 'modelo-separacao';

-- verifica produto volume --

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Consultar Produto', 'consultar-produto');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar-produto'), 'Verifica se Produto é Composto ou Uninatário');

-- volume patrimonio --

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'imprimir'), 'Relatório de Volumes Patrimônio');

--imprimir volume patrimonio --
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Impressão de Volume Patrimônio', 'imprimir-volume-patrimonio');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from RECURSO where NOM_RECURSO like 'expedicao:volume-patrimonio'), (select COD_ACAO from acao where NOM_ACAO like 'imprimir-volume-patrimonio'), 'Impressão do volume patrimonio');

--criado coluna na tabela MODELO_SEPARACAO
ALTER TABLE MODELO_SEPARACAO
ADD (IND_IMPRIME_ETQ_VOLUME VARCHAR(1));

--parametro para definir modelo etiqueta do volume
INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'RELATORIOS E IMPRESSAO'),'MODELO_ETIQUETA_VOLUME', 'Modelo da Etiqueta de Volume','N','A','1');