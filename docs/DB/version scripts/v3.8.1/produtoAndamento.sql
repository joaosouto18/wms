/* Rode primeiro essa linha, caso de problema de unique key, significa que esse arquivo .sql já foi executado anteriormente */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.1', 'produtoAndamento.sql');

CREATE TABLE PRODUTO_ANDAMENTO
(
  NUM_SEQUENCIA NUMBER (8)  NOT NULL ,
  COD_PRODUTO NUMBER (8)  NOT NULL ,
  DSC_GRADE VARCHAR2 (20 BYTE),
  COD_USUARIO NUMBER (8) ,
  DTH_ANDAMENTO DATE ,
  DSC_OBSERVACAO VARCHAR2 (512 BYTE)
) LOGGING
;

ALTER TABLE PRODUTO_ANDAMENTO
ADD CONSTRAINT PRODUTO_ANDAMENTO_PK PRIMARY KEY ( NUM_SEQUENCIA  ) ;

CREATE SEQUENCE SQ_PROD_ANDAMENTO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;