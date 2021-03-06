INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.14.0', '01-integracao-volume-pedido.sql');

CREATE TABLE PEDIDO_PRODUTO_EMB_CLIENTE
  (
    COD_PEDIDO_PRODUTO_EMB_CLIENTE NUMBER(8,0) NOT NULL,
    COD_PEDIDO_PRODUTO             NUMBER(8,0) NOT NULL,
    COD_MAPA_SEPARACAO_EMBALADO    NUMBER(8,0) NOT NULL,
    DSC_LOTE                       VARCHAR2 (200),
    QTD                            NUMBER(13,3) NOT NULL
  );

CREATE SEQUENCE SQ_PEDIDO_PRODUTO_EMB_CLIENTE
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE PEDIDO_PRODUTO_EMB_CLIENTE ADD CONSTRAINT  PK_PEDIDO_PRODUTO_EMB_CLIENTE PRIMARY KEY (COD_PEDIDO_PRODUTO_EMB_CLIENTE);

INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'RETORNO_PRODUTO_EMBALADO','Informar os produtos por embalado no retorno de integração (S/N)','S','A','N');