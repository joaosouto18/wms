-----agruparcargas.sql

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Agrupar Cargas', 'agruparcargas');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'agruparcargas'), 'Agrupar Cargas');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Desagrupar Carga', 'desagruparcarga');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'desagruparcarga'), 'Desagupar Carga');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'RELATORIOS E IMPRESSAO'),'QUEBRA_CARGA_REL_PEND_EXP', 'Quebra Rel. Pendencias da Expedição por Carga (S/N)','N','A','N');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Agrupar Cargas', 0, 'expedicao:agrupar-cargas');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:agrupar-cargas'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Tela de Agrupar Carga');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:agrupar-cargas') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0), 'Agrupar Expedicao', 1, '#', 'N');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Consultar Peso', 'agrupar');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:agrupar-cargas'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'agrupar'), 'Agrupar Carga');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Relatório Disponibilidade de Picking', 0, 'enderecamento:relatorio_disponibilidade-picking');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_disponibilidade-picking'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_disponibilidade-picking') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 54 AND DSC_MENU_ITEM = 'Armazenagem'), 'Disponibilidade de Picking', 10, '#', 'S');

UPDATE MENU_ITEM SET DSC_MENU_ITEM = 'Endereços Vazios' WHERE DSC_MENU_ITEM LIKE '%Disponibilidade de Endereço%';

CREATE TABLE POSICAO_ESTOQUE_RESUMIDO
(
  COD_POSICAO_ESTOQUE       NUMBER (8)  NOT NULL ,
  NUM_RUA                   VARCHAR2 (20 BYTE) NOT NULL ,
  QTD_EXISTENTES            NUMBER (8)  NOT NULL ,
  QTD_OCUPADOS              NUMBER (8)  NOT NULL ,
  QTD_VAZIOS                NUMBER (8)  NOT NULL ,
  OCUPACAO                  NUMBER (8,2)  NOT NULL ,
  DTH_ESTOQUE               DATE
) LOGGING;

CREATE SEQUENCE SQ_POSICAO_ESTOQUE_RESUM_01
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;


----------
