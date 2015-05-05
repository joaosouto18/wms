INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),'RECONFERENCIA_EXPEDICAO', 'Reconferência na Expedição','S','A','N');

CREATE SEQUENCE  SQ_ETIQUETA_CONFERENCIA_01  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

CREATE TABLE ETIQUETA_CONFERENCIA (
  COD_ETIQUETA_CONFERENCIA NUMBER(8) PRIMARY KEY,
  COD_OS_PRIMEIRA_CONFERENCIA NUMBER(8),
  COD_OS_TRANSBORDO NUMBER(8),
  COD_VOLUME_PATRIMONIO NUMBER(8),
  COD_STATUS NUMBER(8) ,
  COD_EXPEDICAO NUMBER(8),
  COD_PEDIDO NUMBER(8),
  COD_ETIQUETA_SEPARACAO NUMBER(8),
  COD_PRODUTO NUMBER(8),
  COD_PRODUTO_VOLUME NUMBER(8),
  COD_PRODUTO_EMBALAGEM NUMBER(8),
  DSC_GRADE VARCHAR(60),  
  DTH_CONFERENCIA DATE,
  DTH_CONFERENCIA_TRANSBORDO DATE
);


INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES ('542', '53', 'PRIMEIRA CONFERENCIA', 'PC');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES ('543', '53', 'SEGUNDA CONFERENCIA', 'SC');
