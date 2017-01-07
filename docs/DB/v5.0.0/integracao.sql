CREATE TABLE CONEXAO_INTEGRACAO
  (
    COD_CONEXAO_INTEGRACAO NUMBER(8,0) NOT NULL,
    DSC_CONEXAO_INTEGRACAO VARCHAR2(100 BYTE),
    SERVIDOR VARCHAR2(100 BYTE) NOT NULL ,
    PORTA    VARCHAR2(100 BYTE) NOT NULL ,
    USUARIO  VARCHAR2(100 BYTE) NOT NULL ,
    SENHA    VARCHAR2(100 BYTE) NOT NULL ,
    DBNAME   VARCHAR2(100 BYTE) NOT NULL ,
    PROVEDOR VARCHAR2(100 BYTE) NOT NULL ,
    PRIMARY KEY (COD_CONEXAO_INTEGRACAO)
  );

CREATE SEQUENCE SQ_CONEXAO_INTEGRACAO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE ACAO_INTEGRACAO
  (
    COD_ACAO_INTEGRACAO      NUMBER(8,0) NOT NULL,
    COD_CONEXAO_INTEGRACAO   NUMBER(8,0) NOT NULL,
    DSC_QUERY                VARCHAR2(4000 BYTE) NOT NULL,
    COD_TIPO_ACAO_INTEGRACAO NUMBER(8,0) NOT NULL,
    IND_UTILIZA_LOG          VARCHAR2(1 BYTE) NOT NULL,
    DTH_ULTIMA_EXECUCAO      DATE ,
    PRIMARY KEY (COD_ACAO_INTEGRACAO)
  );

ALTER TABLE ACAO_INTEGRACAO
ADD CONSTRAINT ACAO_INTEGRACAO_FK_01 FOREIGN KEY ( COD_CONEXAO_INTEGRACAO)
    REFERENCES CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO) ENABLE;

ALTER TABLE ACAO_INTEGRACAO
ADD CONSTRAINT ACAO_INTEGRACAO_FK_02 FOREIGN KEY ( COD_TIPO_ACAO_INTEGRACAO)
    REFERENCES SIGLA (COD_SIGLA) ENABLE;

CREATE SEQUENCE SQ_ACAO_INTEGRACAO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE ACAO_INTEGRACAO_ANDAMENTO
  (
    COD_ACAO_INTEGRACAO_ANDAMENTO NUMBER(8,0) NOT NULL,
    COD_ACAO_INTEGRACAO           NUMBER(8,0) NOT NULL,
    DTH_ANDAMENTO                 DATE NOT NULL,
    IND_SUCESSO                   VARCHAR2(1 BYTE) NOT NULL,
    DSC_OBSERVACAO                VARCHAR2(4000),
    PRIMARY KEY (COD_ACAO_INTEGRACAO_ANDAMENTO)
  );

ALTER TABLE ACAO_INTEGRACAO_ANDAMENTO
ADD CONSTRAINT ACAO_INTEGRACAO_AND_FK_01 FOREIGN KEY (COD_ACAO_INTEGRACAO)
    REFERENCES ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO) ENABLE;

CREATE SEQUENCE SQ_ACAO_INTEGRACAO_AND_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA, DSC_TIPO_SIGLA, IND_SIGLA_SISTEMA) VALUES (SQ_TIPO_SIGLA_01.NEXTVAL, 'INTEGRACAO', 'S');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (600, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'INTEGRACAO'),'PRODUTOS', 'P');

INSERT INTO CONTEXTO_PARAMETRO(COD_CONTEXTO_PARAMETRO, DSC_CONTEXTO_PARAMETRO) VALUES ( SQ_CONTEXTO_PARAMETRO_01.NEXTVAL, 'INTEGRAÇÃO COM WINTHOR');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'INTEGRAÇÃO COM WINTHOR'), 'WINTHOR_CODFILIAL_INTEGRACAO', 'Código da Filial no Winthor', 'S', 'A', '1');

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','integracao.sql');

