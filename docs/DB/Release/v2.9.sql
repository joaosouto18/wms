-- A Partir daqui começa a expedição da caixa azul

ALTER TABLE PRODUTO_EMBALAGEM
ADD (IND_EMBALADO CHAR(1 BYTE) DEFAULT 'N');

CREATE TABLE VOLUME_PATRIMONIO
(
  COD_VOLUME_PATRIMONIO  NUMBER (8) NOT NULL ,
  DSC_VOLUME_PATRIMONIO  VARCHAR2 (20 BYTE) NOT NULL ,
  IND_OCUPADO            CHAR(1 BYTE) DEFAULT 'N'
) LOGGING ;

ALTER TABLE VOLUME_PATRIMONIO ADD CONSTRAINT VOLUME_PK PRIMARY KEY (COD_VOLUME_PATRIMONIO) ;

CREATE SEQUENCE SQ_VOL_PATRIMONIO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE EXPEDICAO_VOLUME_PATRIMONIO
(
  COD_EXPEDICAO_VOLUME  NUMBER (8) NOT NULL,
  COD_VOLUME_PATRIMONIO NUMBER (8) NOT NULL,
  COD_EXPEDICAO         NUMBER (8) NOT NULL,
  COD_TIPO_VOLUME       NUMBER (8),
  DTH_FECHAMENTO        DATE,
  DTH_CONFERIDO         DATE
) LOGGING ;

ALTER TABLE EXPEDICAO_VOLUME_PATRIMONIO ADD CONSTRAINT EXPEDICAO_VOLUME_PK PRIMARY KEY (COD_EXPEDICAO_VOLUME) ;
ALTER TABLE EXPEDICAO_VOLUME_PATRIMONIO ADD CONSTRAINT FK_EXP_VOL_EXP FOREIGN KEY (COD_EXPEDICAO) REFERENCES EXPEDICAO ( COD_EXPEDICAO ) NOT DEFERRABLE ;
ALTER TABLE EXPEDICAO_VOLUME_PATRIMONIO ADD CONSTRAINT FK_EXP_VOL_VOL FOREIGN KEY (COD_VOLUME_PATRIMONIO) REFERENCES VOLUME_PATRIMONIO ( COD_VOLUME_PATRIMONIO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_EXP_VOLUME_PAT_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE ETIQUETA_SEPARACAO
ADD (COD_VOLUME_PATRIMONIO  NUMBER (8));

ALTER TABLE ETIQUETA_SEPARACAO ADD CONSTRAINT FK_ETQ_SEP_EXP_VOL FOREIGN KEY (COD_VOLUME_PATRIMONIO) REFERENCES VOLUME_PATRIMONIO ( COD_VOLUME_PATRIMONIO ) NOT DEFERRABLE ;

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Volume Patrimonio', 0, 'expedicao:volume-patrimonio');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Adicionar');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'), 'Editar');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'imprimir'), 'Imprimir');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Deletar');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'desfazer'), 'Desfazer');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:volume-patrimonio') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'), 'Volume Patrimonio', 10, '#', 'S');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:os'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'list' ), 'Listar itens conferidos' );

<<<<<<< HEAD

---------------------------------------
-- RODAR A PARTIR DAQUI NA SIMONETTI --
-- RODAR VIEWS
---------------------------------------

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DE WEBSERVICE'), 'CONSOME_WEBSERVICE_PRODUTOS', 'Envia os dados logisticos dos produtos por WebService (S/N)', 'N', 'A', 'S');
INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'), 'MODELO_RELATORIOS', 'Modelo dos Relatórios', 'N', 'A', '1');

INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 0 , 'Relatório Listagem de Produtos Sintético' , 'relatorio_listagem-produtos-sintetico' );
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO= 'relatorio_listagem-produtos-sintetico' ), 5, 'Início' );
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO= (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO= 'relatorio_listagem-produtos-sintetico')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 54 AND DSC_MENU_ITEM = 'Recebimento'), 'Listagem Produtos Sintético', 10, '#', '_self', 'S' );

INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'produto'),'Impressão de Etiquetas de Picking', 'imprimir');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'imprimir'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Filtrar');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'imprimir'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'imprimir'), 'Imprimir');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'imprimir') AND COD_ACAO = 5 ), 37,'Imprimir Etiquetas', 10, '#', '_self', 'S');

INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'menu-relatorio'),'Relatório Dados para exportação', 'rel-dados-exportacao');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0 ,54,'Dados para exportação', 10, '#', '_self', 'S');
INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 0 ,'Relatório Expedição para exportação', 'expedicao:relatorio_dados-expedicao');
INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 0 ,'Relatório Recebimento para exportação', 'expedicao:relatorio_dados-recebimento');
INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 0 ,'Relatório Movimentaoção de Produto para exportação', 'expedicao:relatorio_dados-movimentacao');
INSERT INTO RECURSO (COD_RECURSO, COD_RECURSO_PAI, DSC_RECURSO, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 0 ,'Relatório Dados de Produto para exportação', 'expedicao:relatorio_dados-produtos');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-expedicao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-recebimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-produtos'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL,(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-produtos')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Dados para exportação' ), 'Produtos', 10, '#', '_self', 'S' );
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL,(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-recebimento')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Dados para exportação' ), 'Recebimento', 10, '#', '_self', 'S' );
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL,(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-expedicao')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Dados para exportação' ), 'Expedição', 10, '#', '_self', 'S' );
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL,(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_dados-movimentacao')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Dados para exportação' ), 'Movimentação de Produtos', 10, '#', '_self', 'S' );

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:os'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'view' ), 'Exportar itens conferidos' );
