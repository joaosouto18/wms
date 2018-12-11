/* 
 * SCRIPT PARA: Scripts do novo inventário
 * DATA DE CRIAÇÃO: 14/11/2018 
 * CRIADO POR: Tarcísio César
 *
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7', 'xx-novo-inventario.sql');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
VALUES (SQ_RECURSO_01.NEXTVAL, 'Novo Inventário', 0, 'inventario_novo:index');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
         'Novo Inventário'
           );

UPDATE MENU_ITEM SET NUM_PESO = NUM_PESO + 1 WHERE DSC_MENU_ITEM IN ('Importação', 'Relatórios');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0,0,'Novo Inventário',10,'#','_self','S');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
        (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO
         WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index')
           AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
        (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Novo Inventário'),
        'Inventário',
        1, '#', '_self', 'S');

/* CRIAÇÃO DE ACTION DE CRIAÇÃO DE INVENTARIO */

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO)
VALUES (SQ_ACAO_01.NEXTVAL, 'Criar novo inventário', 'criar-inventario');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'criar-inventario'),
         'Criar novo inventário'
);

/* CRIAÇÃO DE MODELOS DE INVENTÁRIO */

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
VALUES (SQ_RECURSO_01.NEXTVAL, 'Modelo de Inventário', 0, 'inventario_novo:modelo-inventario');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
         'Novo Modelo de Inventário'
           );

CREATE TABLE MODELO_INVENTARIO
(
  COD_MODELO_INVENTARIO     NUMBER (8) NOT NULL ,
  DSC_MODELO                VARCHAR2 (100) ,
  IND_ATIVO                CHAR (1) NOT NULL ,
  DTH_CRIACAO               DATE NOT NULL ,
  IND_ITEM_A_ITEM           CHAR (1) NOT NULL ,
  IND_CONTROLA_VALIDADE     CHAR (1) NOT NULL ,
  IND_EXIGE_UMA             CHAR (1) NOT NULL ,
  NUM_CONTAGENS             NUMBER (2) NOT NULL ,
  IND_COMPARA_ESTOQUE       CHAR (1) NOT NULL ,
  IND_USUARIO_N_CONTAGENS   CHAR (1) NOT NULL ,
  IND_CONTAR_TUDO           CHAR (1) NOT NULL ,
  IND_VOLUMES_SEPARADAMENTE CHAR (1) NOT NULL ,
  IND_IMPORTA_ERP           CHAR (1) NOT NULL ,
  ID_MODELO                 NUMBER (1),
  IND_DEFAULT               CHAR (1) NOT NULL
) ;
ALTER TABLE MODELO_INVENTARIO ADD CONSTRAINT MODELO_INVENTARIO_PK PRIMARY KEY ( COD_MODELO_INVENTARIO ) ;

CREATE SEQUENCE SQ_MODELO_INVENTARIO_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_cont_end (
  cod_inv_cont_end           NUMBER(8) NOT NULL,
  cod_inventario_endereco    NUMBER(8) NOT NULL,
  --  Número sequencial para controle interno da contagem
  num_sequencia              NUMBER(3) NOT NULL,
  --  Flag (S/N) que indica se a contagem é de divergência ou não
  ind_contagem_divergencia   CHAR(1) NOT NULL,
  --  Número da contagem atual para apresentação ao usuário
  num_contagem               NUMBER(3) NOT NULL
);

COMMENT ON COLUMN inventario_cont_end.num_sequencia IS
  'Número sequencial para controle interno da contagem';

COMMENT ON COLUMN inventario_cont_end.ind_contagem_divergencia IS
  'Flag (S/N) que indica se a contagem está divergente ou não.';

COMMENT ON COLUMN inventario_cont_end.num_contagem IS
  'Número de apresentação ao usuário a contagem atual';

ALTER TABLE inventario_cont_end ADD CONSTRAINT n_inv_cont_end_pk PRIMARY KEY ( cod_inv_cont_end );


CREATE SEQUENCE SQ_N_INV_CONT_END_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_cont_end_os (
  cod_inv_cont_end_os      NUMBER(8) NOT NULL,
  cod_inv_cont_end         NUMBER(8) NOT NULL,
  cod_os                   NUMBER(8) NOT NULL
);

ALTER TABLE inventario_cont_end_os ADD CONSTRAINT n_inv_cont_end_os_pk PRIMARY KEY ( cod_inv_cont_end_os );

CREATE SEQUENCE SQ_N_INV_CONT_END_OS_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_cont_end_prod (
  cod_inv_cont_end_prod   NUMBER(8) NOT NULL,
  cod_inv_cont_end        NUMBER(8) NOT NULL,
  cod_produto             VARCHAR2(20) NOT NULL,
  dsc_grade               VARCHAR2(100) NOT NULL,
  dsc_lote                VARCHAR2(200),
  --  Quantidade inventariada na menor unidade do produto
  qtd_contada             NUMBER(13,3) NOT NULL,
  cod_produto_embalagem   NUMBER(8),
  --  Fator da embalagem no momento da contagem
  qtd_embalagem           NUMBER(13,3),
  --  Código de barras do produto no momento da contagem
  cod_barras              VARCHAR2(128),
  cod_produto_volume      NUMBER(8),
  --  Flag (S/N) que indica se a contagem está divergente ou não.
  ind_divergente          CHAR(1),
  --  Data de validade do produto
  dth_validade            DATE,
  dth_contagem            DATE NOT NULL
);

COMMENT ON COLUMN inventario_cont_end_prod.qtd_contada IS
  'Quantidade inventariada na menor unidade do produto';

COMMENT ON COLUMN inventario_cont_end_prod.qtd_embalagem IS
  'Fator da embalagem no momento da contagem';

COMMENT ON COLUMN inventario_cont_end_prod.cod_barras IS
  'Código de barras do produto no momento da contagem';

COMMENT ON COLUMN inventario_cont_end_prod.ind_divergente IS
  'Flag (S/N) que indica se a contagem está divergente ou não.';

COMMENT ON COLUMN inventario_cont_end_prod.dth_validade IS
  'Data de validade do produto';

COMMENT ON COLUMN inventario_cont_end_prod.dth_contagem IS
  'Data de criação do registro';

ALTER TABLE inventario_cont_end_prod ADD CONSTRAINT n_inv_cont_end_prod_pk PRIMARY KEY ( cod_inv_cont_end_prod );

CREATE SEQUENCE SQ_N_INV_CONT_END_PROD_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_end_prod (
  cod_inv_end_prod          NUMBER(8) NOT NULL,
  cod_inventario_endereco   NUMBER(8) NOT NULL,
  cod_produto               VARCHAR2(20) NOT NULL,
  dsc_grade                 VARCHAR2(100) NOT NULL
);

ALTER TABLE inventario_end_prod ADD CONSTRAINT n_inv_end_prod_pk PRIMARY KEY ( cod_inv_end_prod );

CREATE SEQUENCE SQ_N_INV_END_PROD_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_endereco_novo (
  cod_inventario_endereco   NUMBER(8) NOT NULL,
  cod_inventario            NUMBER(8) NOT NULL,
  cod_deposito_endereco     NUMBER(8) NOT NULL,
  num_contagem              NUMBER(3) NOT NULL,
  --  Flag (S/N) para indicar  se o inventário no endereço foi finalizado
  ind_finalizado            CHAR(1) NOT NULL
);

COMMENT ON COLUMN inventario_endereco_novo.ind_finalizado IS
  'Flag (S/N) para indicar  se o inventário no endereço foi finalizado';

ALTER TABLE inventario_endereco_novo ADD CONSTRAINT inv_end_novo_pk PRIMARY KEY ( cod_inventario_endereco );

CREATE SEQUENCE SQ_N_INV_END_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE MODELO_INVENTARIO(
  COD_MODELO_INVENTARIO     NUMBER(8,0) NOT NULL,
	DSC_MODELO                VARCHAR(100),
	IND_ATIVO                 CHAR(1) NOT NULL,
	DTH_CRIACAO               DATE NOT NULL,
	IND_ITEM_A_ITEM           CHAR(1) NOT NULL,
	IND_CONTROLA_VALIDADE     CHAR(1) NOT NULL,
	IND_EXIGE_UMA             CHAR(1) NOT NULL,
	NUM_CONTAGENS             NUMBER(2,0) NOT NULL,
	IND_COMPARA_ESTOQUE       CHAR(1) NOT NULL,
	IND_USUARIO_N_CONTAGENS   CHAR(1) NOT NULL,
	IND_CONTAR_TUDO           CHAR(1) NOT NULL,
	IND_VOLUMES_SEPARADAMENTE CHAR(1) NOT NULL,
	IND_DEFAULT               CHAR(1) NOT NULL
);

ALTER TABLE MODELO_INVENTARIO ADD CONSTRAINT "MODELO_INVENTARIO_PK" PRIMARY KEY (COD_MODELO_INVENTARIO);

CREATE TABLE inventario_novo (
  cod_inventario       NUMBER(8) NOT NULL,
  dsc_inventario       VARCHAR2(100),
  dth_inicio           DATE NOT NULL,
  dth_finalizacao      DATE,
  cod_status           NUMBER(4) NOT NULL,
  cod_inventario_erp   NUMBER(8) ,
  COD_MODELO_INVENTARIO     NUMBER (8) NOT NULL ,
  IND_ITEM_A_ITEM           CHAR (1) NOT NULL ,
  IND_CONTROLA_VALIDADE     CHAR (1) NOT NULL ,
  IND_EXIGE_UMA             CHAR (1) NOT NULL ,
  NUM_CONTAGENS             NUMBER (2) NOT NULL ,
  IND_COMPARA_ESTOQUE       CHAR (1) NOT NULL ,
  IND_USUARIO_N_CONTAGENS   CHAR (1) NOT NULL ,
  IND_CONTAR_TUDO           CHAR (1) NOT NULL ,
  IND_VOLUMES_SEPARADAMENTE CHAR (1) NOT NULL ,
  IND_IMPORTA_ERP           CHAR (1) NOT NULL ,
  ID_MODELO                 NUMBER (1)
);

ALTER TABLE inventario_novo ADD CONSTRAINT inv_novo_pk PRIMARY KEY ( cod_inventario );
ALTER TABLE INVENTARIO_NOVO ADD CONSTRAINT FK_INVNV_MODINV FOREIGN KEY ( COD_MODELO_INVENTARIO ) REFERENCES MODELO_INVENTARIO ( COD_MODELO_INVENTARIO ) ;

CREATE SEQUENCE SQ_N_INV_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

ALTER TABLE inventario_cont_end_os
  ADD CONSTRAINT fk_invcontendos_invcontend FOREIGN KEY ( cod_inv_cont_end )
REFERENCES inventario_cont_end ( cod_inv_cont_end );

ALTER TABLE inventario_cont_end_os
  ADD CONSTRAINT fk_invcontendos_os FOREIGN KEY ( cod_os )
REFERENCES ordem_servico ( cod_os );

ALTER TABLE inventario_cont_end_prod
  ADD CONSTRAINT fk_invcontendprod_invcontend FOREIGN KEY ( cod_inv_cont_end )
REFERENCES inventario_cont_end ( cod_inv_cont_end );

ALTER TABLE inventario_cont_end_prod
  ADD CONSTRAINT fk_invcontendprod_prod FOREIGN KEY ( cod_produto, dsc_grade )
REFERENCES produto ( cod_produto, dsc_grade );

ALTER TABLE inventario_cont_end_prod
  ADD CONSTRAINT fk_invcontendprod_prodemb FOREIGN KEY ( cod_produto_embalagem )
REFERENCES produto_embalagem ( cod_produto_embalagem );

ALTER TABLE inventario_cont_end_prod
  ADD CONSTRAINT fk_invcontendprod_prodvol FOREIGN KEY ( cod_produto_volume )
REFERENCES produto_volume ( cod_produto_volume );

ALTER TABLE inventario_cont_end
  ADD CONSTRAINT fk_invcontenv_invend FOREIGN KEY ( cod_inventario_endereco )
REFERENCES inventario_endereco_novo ( cod_inventario_endereco );

ALTER TABLE inventario_endereco_novo
  ADD CONSTRAINT fk_invendnv_depend FOREIGN KEY ( cod_deposito_endereco )
REFERENCES deposito_endereco ( cod_deposito_endereco );

ALTER TABLE inventario_endereco_novo
  ADD CONSTRAINT fk_invendnv_invnv FOREIGN KEY ( cod_inventario )
REFERENCES inventario_novo ( cod_inventario );

ALTER TABLE inventario_end_prod
  ADD CONSTRAINT fk_invendprod_invend FOREIGN KEY ( cod_inventario_endereco )
REFERENCES inventario_endereco_novo ( cod_inventario_endereco );

ALTER TABLE inventario_end_prod
  ADD CONSTRAINT fk_invendprod_prod FOREIGN KEY ( cod_produto, dsc_grade )
REFERENCES produto ( cod_produto, dsc_grade );