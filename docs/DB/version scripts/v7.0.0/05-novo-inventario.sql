/* 
 * SCRIPT PARA: Scripts do novo inventário
 * DATA DE CRIAÇÃO: 14/11/2018 
 * CRIADO POR: Tarcísio César
 *
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7', '05-novo-inventario.sql');

/*   CRIAÇÃO DO ACESSO AO INVENTARIO NOVO   */
INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
VALUES (SQ_RECURSO_01.NEXTVAL, 'Novo Inventário', 0, 'inventario_novo:index');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
         'Lista de inventários'
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

/* CRIAÇÃO DE ACTION DE LIBERAÇÃO DO INVENTÁRIO */

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
VALUES (SQ_RECURSO_ACAO_01.nextval,
        (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
        (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'liberar'), 'Liberar Inventário');

/* CRIAÇÃO DE ACTION DE REMOÇÃO DE ENDEREÇO/PRODUTO DO INVENTÁRIO */

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO)
VALUES (SQ_ACAO_01.nextval, 'Remover Endereço', 'remover-endereco');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
VALUES (SQ_RECURSO_ACAO_01.nextval,
        (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
        (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'remover-endereco'), 'Remover Endereço');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO)
VALUES (SQ_ACAO_01.nextval, 'Remover Produto', 'remover-produto');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
VALUES (SQ_RECURSO_ACAO_01.nextval,
        (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
        (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'remover-produto'), 'Remover Produto');

/* CRIAÇÃO DE ACTION DE CRIAÇÃO DE INVENTARIO */

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO)
VALUES (SQ_ACAO_01.NEXTVAL, 'Criar novo inventário', 'criar-inventario');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'criar-inventario'),
         'Criar novo inventário'
);

/* CRIAÇÃO DE ACTION DE ATUALIZAÇÃO DE ESTOQUE */
INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'atualizar'),
         'Atualizar estoque'
);

/* CRIAÇÃO DE ACTION DE INTERROMPER DE INVENTARIO */

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO)
VALUES (SQ_ACAO_01.NEXTVAL, 'Interromper', 'interromper');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'interromper'),
         'Interromper inventário'
       );

/* CRIAÇÃO DE ACTION DE CANCELAR DE INVENTARIO */

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:index'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'cancelar'),
         'Cancelar inventário'
       );

/* CRIAÇÃO DE MODELOS DE INVENTÁRIO */

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
VALUES (SQ_RECURSO_01.NEXTVAL, 'Modelo de Inventário', 0, 'inventario_novo:modelo-inventario');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
         'Modelo de Inventário'
           );

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'),
         'Criar novo modelo de inventário'
       );

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'),
         'Editar modelo de inventário'
         );

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'),
         'Deletar modelo de inventário'
       );

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
        (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO
         WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:modelo-inventario')
           AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
        (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'),
        'Modelo Inventário',
        7, '#', '_self', 'S');

ALTER TABLE
   HISTORICO_ESTOQUE
ADD
   COD_OPERACAO  NUMBER(8);

CREATE TABLE MODELO_INVENTARIO
(
  COD_MODELO_INVENTARIO     NUMBER (8) NOT NULL ,
  DSC_MODELO                VARCHAR2 (100) ,
  COD_USUARIO               NUMBER (8) NOT NULL ,
  IND_ATIVO                 CHAR (1) NOT NULL ,
  DTH_CRIACAO               DATE NOT NULL ,
  IND_ITEM_A_ITEM           CHAR (1)  ,
  IND_CONTROLA_VALIDADE     CHAR (1) NOT NULL ,
  IND_EXIGE_UMA             CHAR (1)  ,
  NUM_CONTAGENS             NUMBER (2) NOT NULL ,
  IND_COMPARA_ESTOQUE       CHAR (1) NOT NULL ,
  IND_USUARIO_N_CONTAGENS   CHAR (1) NOT NULL ,
  IND_CONTAR_TUDO           CHAR (1) NOT NULL ,
  IND_VOLUMES_SEPARADAMENTE CHAR (1) NOT NULL ,
  IND_DEFAULT               CHAR (1) NOT NULL
) ;
ALTER TABLE MODELO_INVENTARIO ADD CONSTRAINT MODELO_INVENTARIO_PK PRIMARY KEY ( COD_MODELO_INVENTARIO ) ;

ALTER TABLE modelo_inventario
  ADD CONSTRAINT fk_modelo_usuario FOREIGN KEY ( cod_usuario )
    REFERENCES usuario ( cod_usuario )
      NOT DEFERRABLE;

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
  dsc_grade                 VARCHAR2(100) NOT NULL,
  ind_ativo                 CHAR(1) NOT NULL
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
  cod_status                NUMBER(4) NOT NULL,
  ind_ativo                 CHAR(1) NOT NULL
);


ALTER TABLE inventario_endereco_novo ADD CONSTRAINT inv_end_novo_pk PRIMARY KEY ( cod_inventario_endereco );

CREATE SEQUENCE SQ_N_INV_END_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;

CREATE TABLE inventario_novo (
  cod_inventario            NUMBER(8) NOT NULL,
  dsc_inventario            VARCHAR2(100),
  ind_criterio              CHAR(1) NOT NULL,
  dth_criacao               DATE NOT NULL,
  dth_inicio                DATE,
  dth_finalizacao           DATE,
  cod_status                NUMBER(4) NOT NULL,
  cod_inventario_erp        NUMBER(8) ,
  COD_MODELO_INVENTARIO     NUMBER (8) NOT NULL ,
  IND_ITEM_A_ITEM           CHAR (1) ,
  IND_CONTROLA_VALIDADE     CHAR (1) NOT NULL ,
  IND_EXIGE_UMA             CHAR (1) ,
  NUM_CONTAGENS             NUMBER (2) NOT NULL ,
  IND_COMPARA_ESTOQUE       CHAR (1) NOT NULL ,
  IND_USUARIO_N_CONTAGENS   CHAR (1) NOT NULL ,
  IND_CONTAR_TUDO           CHAR (1) NOT NULL ,
  IND_VOLUMES_SEPARADAMENTE CHAR (1) NOT NULL
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

CREATE TABLE INVENTARIO_ANDAMENTO
  (
    COD_INVENTARIO_ANDAMENTO NUMBER (8) NOT NULL ,
    DTH_ACAO                 DATE ,
    COD_ACAO                 NUMBER (8) ,
    DESCRICAO                VARCHAR2(100) ,
    COD_INVENTARIO           NUMBER (8) NOT NULL ,
    COD_USUARIO              NUMBER (8)
  ) ;

CREATE SEQUENCE SQ_INVENTARIO_ANDAMENTO_01
    INCREMENT BY 1
    START WITH 1
    MAXVALUE 999999999999999999999999999
    MINVALUE 0
    NOCYCLE
    NOCACHE
    NOORDER;

ALTER TABLE INVENTARIO_ANDAMENTO
  ADD CONSTRAINT INVENTARIO_ANDAMENTO_PK
  PRIMARY KEY ( COD_INVENTARIO_ANDAMENTO ) ;
ALTER TABLE INVENTARIO_ANDAMENTO
  ADD CONSTRAINT INV_ANDAMENTO_INV_NOVO_FK
  FOREIGN KEY ( COD_INVENTARIO )
REFERENCES INVENTARIO_NOVO ( COD_INVENTARIO ) ;

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

CREATE TABLE modelo_inventario_log (
   cod_log                          NUMBER(8) NOT NULL,
   cod_modelo_inventario            NUMBER(8) NOT NULL,
   cod_usuario                      NUMBER(8) NOT NULL,
   dth_registro                     DATE NOT NULL,
   dsc_modelo_de                    VARCHAR2(100),
   dsc_modelo_para                  VARCHAR2(100),
   ind_ativo_de                     CHAR(1) NOT NULL,
   ind_ativo_para                   CHAR(1) NOT NULL,
   ind_item_a_item_de               CHAR(1) ,
   ind_item_a_item_para             CHAR(1) ,
   ind_controla_validade_de         CHAR(1) NOT NULL,
   ind_controla_validade_para       CHAR(1) NOT NULL,
   ind_exige_uma_de                 CHAR(1) ,
   ind_exige_uma_para               CHAR(1) ,
   num_contagens_de                 NUMBER(2) NOT NULL,
   num_contagens_para               NUMBER(2) NOT NULL,
   ind_compara_estoque_de           CHAR(1) NOT NULL,
   ind_compara_estoque_para         CHAR(1) NOT NULL,
   ind_usuario_n_contagens_de       CHAR(1) NOT NULL,
   ind_usuario_n_contagens_para     CHAR(1) NOT NULL,
   ind_contar_tudo_de               CHAR(1) NOT NULL,
   ind_contar_tudo_para             CHAR(1) NOT NULL,
   ind_volumes_separadamente_de     CHAR(1) NOT NULL,
   ind_volumes_separadamente_para   CHAR(1) NOT NULL,
   ind_default_de                   CHAR(1) NOT NULL,
   ind_default_para                 CHAR(1) NOT NULL
)
  LOGGING;

ALTER TABLE modelo_inventario_log ADD CONSTRAINT log_modelo_inventario_pk PRIMARY KEY ( cod_log );

ALTER TABLE modelo_inventario_log
  ADD CONSTRAINT fk_log_modelo FOREIGN KEY ( cod_modelo_inventario )
    REFERENCES modelo_inventario ( cod_modelo_inventario )
      NOT DEFERRABLE;

ALTER TABLE modelo_inventario_log
  ADD CONSTRAINT fk_log_usuario FOREIGN KEY ( cod_usuario )
    REFERENCES usuario ( cod_usuario )
      NOT DEFERRABLE;

CREATE SEQUENCE SQ_MODELO_INVENTARIO_LOG_01
  INCREMENT BY 1
  START WITH 1
  MAXVALUE 999999999999999999999999999
  MINVALUE 0
  NOCYCLE
  NOCACHE
  NOORDER;


ALTER TABLE MODELO_SEPARACAO ADD (PRODUTO_EM_INVENTARIO CHAR(1) DEFAULT 'N' NOT NULL);