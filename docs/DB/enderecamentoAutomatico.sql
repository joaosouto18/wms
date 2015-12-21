INSERT INTO RECURSO (DSC_RECURSO, COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES ('Prioridade Recursos', SQ_RECURSO_01.NEXTVAL, 0, 'enderecamento:modelo') ;

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Prioridade de Estrutura de Armazenagem');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO IN (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO like 'enderecamento:modelo') AND COD_ACAO IN (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_MENU_ITEM IN (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros')), 'Modelo de Armazenagem', 10, '#', '_self', 'S');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'), 'Prioridade de Estrutura de Armazenagem');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'), 'Prioridade de Estrutura de Armazenagem');

CREATE
  TABLE MODELO_ENDERECAMENTO
  (
    COD_MODELO_ENDERECAMENTO NUMBER (8) NOT NULL ,
    DSC_MODELO_ENDERECAMENTO VARCHAR2 (128 BYTE) ,
    COD_MODELO_REFERENCIA    NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_ENDERECAMENTO ADD CONSTRAINT MODELO_ENDERECAMENTO_PK PRIMARY
KEY ( COD_MODELO_ENDERECAMENTO ) ;

CREATE
  TABLE MODELO_END_AREA_ARMAZ
  (
    COD_MODELO_END_AREA_ARMAZ NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO  NUMBER (8) NOT NULL ,
    COD_AREA_ARMAZENAGEM      NUMBER (8) NOT NULL ,
    COD_PRIORIDADE            NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT MODELO_END_AREA_ARMAZ_PK
PRIMARY KEY ( COD_MODELO_END_AREA_ARMAZ ) ;

CREATE
  TABLE MODELO_END_EST_ARMAZ
  (
    COD_MODELO_END_EST_ARMAZ NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO NUMBER (8) NOT NULL ,
    COD_TIPO_EST_ARMAZ       NUMBER (8) NOT NULL ,
    COD_PRIORIDADE           NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT MODELO_END_EST_ARMAZ_PK PRIMARY
KEY ( COD_MODELO_END_EST_ARMAZ ) ;

CREATE
  TABLE MODELO_END_TIPO_ENDERECO
  (
    COD_MODELO_END_TIPO_ENDERECO NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO     NUMBER (8) NOT NULL ,
    COD_TIPO_ENDERECO            NUMBER (8) NOT NULL ,
    COD_PRIORIDADE               NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT MODELO_END_TIPO_ENDERECO_PK
PRIMARY KEY ( COD_MODELO_END_TIPO_ENDERECO ) ;

ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT M_END_AREA_ARMAZENAGEM_FK
FOREIGN KEY ( COD_AREA_ARMAZENAGEM ) REFERENCES AREA_ARMAZENAGEM (
COD_AREA_ARMAZENAGEM ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_AREA_ARMAZ ADD CONSTRAINT M_END_MODELO_ENDERECAMENTO_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT M_END_EST_ARMAZ_M_END_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_EST_ARMAZ ADD CONSTRAINT M_END_TIPO_EST_ARMAZ_FK FOREIGN
KEY ( COD_TIPO_EST_ARMAZ ) REFERENCES TIPO_EST_ARMAZ (
COD_TIPO_EST_ARMAZ ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT TIPO_ENDERECO_FK FOREIGN
KEY ( COD_TIPO_ENDERECO ) REFERENCES TIPO_ENDERECO (
COD_TIPO_ENDERECO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_TIPO_ENDERECO ADD CONSTRAINT M_END_TIPO_ENDERECO_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_MODELO_ENDERECAMENTO_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_AREA_ARMAZ_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_EST_ARMAZ_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
CREATE SEQUENCE SQ_MODELO_END_TIPO_ENDERECO_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:modelo'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'), 'Prioridade de Estrutura de Armazenagem');



CREATE TABLE PRODUTO_END_TIPO_EST_ARMAZ
  (
    COD_PRODUTO_END_TIPO_EST_ARMAZ NUMBER (8) NOT NULL ,
    COD_TIPO_EST_ARMAZ             NUMBER (8) NOT NULL ,
    COD_PRODUTO                    VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                      VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE                 NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PRODUTO_END_TIPO_EST_ARMAZ_PK PRIMARY KEY ( COD_PRODUTO_END_TIPO_EST_ARMAZ ) ;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PROD_END_TP_EST_ARMAZ_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_TIPO_EST_ARMAZ
  ADD CONSTRAINT PROD_END_TP_EST_ARMAZ_TPEST_FK FOREIGN KEY ( COD_TIPO_EST_ARMAZ ) REFERENCES TIPO_EST_ARMAZ ( COD_TIPO_EST_ARMAZ ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_TIPO_EST_ARMAZ
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE PRODUTO_END_AREA_ARMAZENAGEM
  (
    COD_PRODUTO_END_AREA_ARMAZ   NUMBER (8) NOT NULL ,
    COD_AREA_ARMAZENAGEM             NUMBER (8) NOT NULL ,
    COD_PRODUTO                  VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                    VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE               NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_PK PRIMARY KEY ( COD_PRODUTO_END_AREA_ARMAZ ) ;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_AREA_ARMAZENAGEM
  ADD CONSTRAINT PRODUTO_END_AREA_ARMAZ_AREA_FK FOREIGN KEY ( COD_AREA_ARMAZENAGEM ) REFERENCES AREA_ARMAZENAGEM ( COD_AREA_ARMAZENAGEM ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_AREA_ARMAZENAGEM
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

CREATE TABLE PRODUTO_END_TIPO_ENDERECO
  (
    COD_PRODUTO_END_TIPO_ENDERECO   NUMBER (8) NOT NULL ,
    COD_TIPO_ENDERECO             NUMBER (8) NOT NULL ,
    COD_PRODUTO                  VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                    VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE               NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PRODUTO_END_TIPO_ENDERECO_PK PRIMARY KEY ( COD_PRODUTO_END_TIPO_ENDERECO ) ;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PROD_END_TIPO_ENDERECO_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_TIPO_ENDERECO
  ADD CONSTRAINT PROD_END_TIPO_END_TPEND_FK FOREIGN KEY ( COD_TIPO_ENDERECO ) REFERENCES TIPO_ENDERECO ( COD_TIPO_ENDERECO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_TIPO_ENDERECO
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE PRODUTO
  ADD ( COD_ENDERECO_REF_END_AUTO NUMBER (8));

ALTER TABLE PRODUTO
  ADD CONSTRAINT PRODUTO_ENDERECO_REFERENCIA_FK FOREIGN KEY ( COD_ENDERECO_REF_END_AUTO ) REFERENCES DEPOSITO_ENDERECO ( COD_DEPOSITO_ENDERECO ) NOT DEFERRABLE ;

ALTER TABLE RECEBIMENTO
  ADD ( COD_MODELO_ENDERECAMENTO NUMBER (8));

ALTER TABLE RECEBIMENTO
  ADD CONSTRAINT RECEBIMENTO_MODELO_ENDERE_FK FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO ( COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
     VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'ENDERECAMENTO'),'MODELO_ENDERECAMENTO_PADRAO', 'Modelo de Endereçamento Padrão','S','A','1');


--rodar a partir dessa linha data 07/10/2015
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'obter Mapas de separação Pendentes', 'pendentes-conferencia');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:mapa'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'pendentes-conferencia'), 'Exibir Mapas pendentes de Conferencia');




CREATE TABLE PRODUTO_END_CARACT_END
  (
    COD_PRODUTO_END_CARACT_END   NUMBER (8) NOT NULL ,
    COD_CARACTERISTICA_ENDERECO  NUMBER (8) NOT NULL ,
    COD_PRODUTO                  VARCHAR2 (20) NOT NULL ,
    DSC_GRADE                    VARCHAR2 (20) NOT NULL ,
    NUM_PRIORIDADE               NUMBER (8)
  ) ;

ALTER TABLE PRODUTO_END_CARACT_END
  ADD CONSTRAINT PRODUTO_END_CARACT_END_PK PRIMARY KEY ( COD_PRODUTO_END_CARACT_END ) ;

ALTER TABLE PRODUTO_END_CARACT_END
  ADD CONSTRAINT PRODUTO_END_CARACT_END_PROD_FK FOREIGN KEY (COD_PRODUTO, DSC_GRADE) REFERENCES PRODUTO (COD_PRODUTO,DSC_GRADE) NOT DEFERRABLE;

ALTER TABLE PRODUTO_END_CARACT_END
  ADD CONSTRAINT PRODUTO_END_CARACT_CARAC_FK FOREIGN KEY ( COD_CARACTERISTICA_ENDERECO ) REFERENCES CARACTERISTICA_ENDERECO ( COD_CARACTERISTICA_ENDERECO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_PROD_END_CARACT_END
INCREMENT BY 1
START WITH 1
MAXVALUE 999999999999999999999999999
MINVALUE 0
NOCYCLE
NOCACHE
NOORDER;

CREATE
  TABLE MODELO_END_CARACT_END
  (
    COD_MODELO_END_CARACT_END   NUMBER (8) NOT NULL ,
    COD_MODELO_ENDERECAMENTO    NUMBER (8) NOT NULL ,
    COD_CARACTERISTICA_ENDERECO NUMBER (8) NOT NULL ,
    COD_PRIORIDADE              NUMBER (8)
  )
  LOGGING ;
ALTER TABLE MODELO_END_CARACT_END ADD CONSTRAINT MODELO_END_CARACT_END_PK
PRIMARY KEY ( COD_MODELO_END_CARACT_END ) ;

ALTER TABLE MODELO_END_CARACT_END ADD CONSTRAINT MODELO_END_CARACT_END_CARAC_FK
FOREIGN KEY ( COD_CARACTERISTICA_ENDERECO ) REFERENCES CARACTERISTICA_ENDERECO (
COD_CARACTERISTICA_ENDERECO ) NOT DEFERRABLE ;

ALTER TABLE MODELO_END_CARACT_END ADD CONSTRAINT MODELO_END_CARACT_END_MOD_FK
FOREIGN KEY ( COD_MODELO_ENDERECAMENTO ) REFERENCES MODELO_ENDERECAMENTO (
COD_MODELO_ENDERECAMENTO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_MODELO_END_CARACT_END_01 MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;






--alteração feita dia 27/10/15
INSERT INTO ACAO (COD_ACAO,DSC_ACAO,NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Equipe Carregamento Expedição', 'equipe-carregamento');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'equipe-carregamento'), 'Equipe Carregamento Expedicao');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'equipe-carregamento')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Expedição' AND COD_PAI = 0), 'Equipe Expedição', 7, '#', '_self', 'S');
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Alterar Endereço', 'alterar-endereco');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'enderecamento:movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'alterar-endereco'), 'Alterar Endereço UMA');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'enderecamento:movimentacao') and cod_acao = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'alterar-endereco')),(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Armazenagem' AND COD_PAI = 0), 'Alterar Endereço', 7, '#', '_self', 'S');
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Manual', 'manual');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'inventario:parcial'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'manual'), 'Inventário Manual');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO LIKE (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'inventario:parcial') AND COD_ACAO LIKE (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'manual')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM LIKE 'Inventário' AND COD_PAI = 0), 'Inventário Manual', 3, '#', '_self', 'S');


--alteração feita dia 10/11/2015
ALTER TABLE EXPEDICAO_VOLUME_PATRIMONIO ADD (COD_USUARIO NUMBER (8));

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
     VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),'PERFIL_EQUIPE_RECEBIMENTO_TRANSBORDO', 'Código do Perfil da Equipe de Receb. Transbordo','S','A','0');

CREATE TABLE EQUIPE_RECEBIMENTO_TRANSBORDO
(
  COD_EQUIPE_TRANSBORDO                   NUMBER (8)  NOT NULL ,
  COD_EXPEDICAO                           NUMBER (8)  NOT NULL ,
  COD_USUARIO                             NUMBER (8)  NOT NULL ,
  DTH_VINCULO                             DATE
) LOGGING;

CREATE SEQUENCE SQ_EQUIPE_TRANSB_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE EQUIPE_RECEBIMENTO_TRANSBORDO ADD CONSTRAINT EQP_TRANSB_PK PRIMARY KEY ( COD_EQUIPE_TRANSBORDO ) ;
ALTER TABLE EQUIPE_RECEBIMENTO_TRANSBORDO ADD CONSTRAINT EQP_TRANSB_EXP_FK FOREIGN KEY ( COD_EXPEDICAO ) REFERENCES EXPEDICAO ( COD_EXPEDICAO ) NOT DEFERRABLE ;
ALTER TABLE EQUIPE_RECEBIMENTO_TRANSBORDO ADD CONSTRAINT EQP_TRANSB__USU_FK FOREIGN KEY ( COD_USUARIO ) REFERENCES USUARIO ( COD_USUARIO ) NOT DEFERRABLE ;

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'), 'PERFIL_EQUIPE_RECEBIMENTO', 'Código do Perfil da equipe de Recebimento', 'S', 'A', '0');

CREATE TABLE EQUIPE_EXPEDICAO_TRANSBORDO
(
  COD_EQUIPE_TRANSBORDO                   NUMBER (8)  NOT NULL ,
  COD_EXPEDICAO                           NUMBER (8)  NOT NULL ,
  COD_USUARIO                             NUMBER (8)  NOT NULL ,
  DTH_VINCULO                             DATE
) LOGGING;

ALTER TABLE EQUIPE_EXPEDICAO_TRANSBORDO ADD CONSTRAINT EQP_EXP_TRANSB_PK PRIMARY KEY ( COD_EQUIPE_TRANSBORDO ) ;
ALTER TABLE EQUIPE_EXPEDICAO_TRANSBORDO ADD CONSTRAINT EQP_EXP_TRANSB_EXP_FK FOREIGN KEY ( COD_EXPEDICAO ) REFERENCES EXPEDICAO ( COD_EXPEDICAO ) NOT DEFERRABLE ;
ALTER TABLE EQUIPE_EXPEDICAO_TRANSBORDO ADD CONSTRAINT EQP_EXP_TRANSB__USU_FK FOREIGN KEY ( COD_USUARIO ) REFERENCES USUARIO ( COD_USUARIO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_EQUIPE_EXP_TRANSB_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'), 'PERFIL_EQUIPE_EXPEDICAO_TRANSBORDO', 'Código do Perfil da equipe de Exp. Transbordo', 'S', 'A', '0');

ALTER TABLE EQUIPE_EXPEDICAO_TRANSBORDO ADD (DSC_PLACA_CARGA VARCHAR2 (255));

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'), 'PERFIL_EQUIPE_EXPEDICAO', 'Código do Perfil da equipe de Expedição', 'S', 'A', '0');
DELETE FROM MENU_ITEM where COD_RECURSO_ACAO = (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'manual'));
DELETE FROM RECURSO_ACAO WHERE COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'manual');
DELETE FROM MENU_ITEM where COD_RECURSO_ACAO = (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO where COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'alterar-endereco'));
DELETE FROM RECURSO_ACAO WHERE COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'alterar-endereco');

ALTER TABLE PALETE ADD (TIPO_ENDERECAMENTO CHAR(1) DEFAULT 'M');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
     VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),'PERFIL_EQUIPE_CARREGAMENTO', 'Código do Perfil da Equipe de Carregamento','S','A','0');

delete from parametro where DSC_TITULO_PARAMETRO like 'Código do Perfil da equipe de Expedição';
