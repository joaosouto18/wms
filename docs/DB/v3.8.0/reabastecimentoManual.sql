INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.0', 'reabastecimentoManual.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
     VALUES (SQ_PARAMETRO_01.NEXTVAL,
     (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'ENDERECAMENTO'),
     'REABASTECIMENTO_MANUAL', 'Reabastecimento Manual','S','A','N');

CREATE TABLE "REABASTECIMENTO_MANUAL"
("COD_REABASTECIMENTO_MANUAL" NUMBER(8,0) NOT NULL ENABLE,
"COD_DEPOSITO_ENDERECO" NUMBER(8,0),
"COD_OS" NUMBER(8,0),
"COD_PRODUTO" VARCHAR2(20 BYTE),
"QTD" NUMBER(8,0),
"DTH_COLETA" TIMESTAMP (6),
 CONSTRAINT "REABASTECIMENTO_MANUAL_PK" PRIMARY KEY ("COD_REABASTECIMENTO_MANUAL")
 );

CREATE SEQUENCE SQ_REABASTECIMENTO_MANUAL_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER
;

INSERT INTO "ATIVIDADE" (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES ('16', 'Reabastecimento Manual', '1')
