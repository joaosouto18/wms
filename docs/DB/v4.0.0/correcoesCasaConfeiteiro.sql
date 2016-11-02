INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'),'PERMITE_CLIENTES_CNPJ_IGUAIS','Permitir Clientes com o mesmo CPF/CNPJ','S','A','N');

ALTER TABLE MODELO_SEPARACAO
  ADD UTILIZA_VOLUME_PATRIMONIO CHAR(1) NULL;

CREATE TABLE MAPA_SEPARACAO_EMB_CLIENTE
  (
    COD_MAPA_SEPARACAO_EMB_CLIENTE NUMBER(8,0) NOT NULL,
    COD_PESSOA NUMBER(8,0) NOT NULL,
    COD_MAPA_SEPARACAO NUMBER(8,0) NOT NULL,
    COD_STATUS NUMBER(8,0) NOT NULL,
    NUM_SEQUENCIA NUMBER(8,0) NULL
  );

CREATE SEQUENCE SQ_MAPA_SEPARACAO_EMBALADO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA, DSC_TIPO_SIGLA, IND_SIGLA_SISTEMA) VALUES (SQ_TIPO_SIGLA_01.NEXTVAL, 'EMBALADOS', 'S');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (SQ_SIGLA_01.NEXTVAL, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'EMBALADOS'),'CONFERENCIA EMBALADO INICIADA', 'CE');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (SQ_SIGLA_01.NEXTVAL, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'EMBALADOS'),'CONFERENCIA EMBALADO FINALIZADO', 'CE');

ALTER TABLE MAPA_SEPARACAO_CONFERENCIA ADD (COD_MAPA_SEPARACAO_EMBALADO NUMBER(8,0) NULL);