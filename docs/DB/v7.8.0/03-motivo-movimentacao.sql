/* 
 * SCRIPT PARA: Criação de campos e tabela para especificação do motivo da movimentação manual
 * DATA DE CRIAÇÃO: 02/08/2019 
 * CRIADO POR: Tarcísio César
 *
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.8', '03-motivo-movimentacao.sql');

CREATE TABLE MOTIVO_MOVIMENTACAO
(
    COD_MOTIVO_MOVIMENTACAO NUMBER(8) NOT NULL
        CONSTRAINT PK_MOTIVO_MOVIMENTACAO
            PRIMARY KEY ,
    DSC_MOTIVO_MOVIMENTACAO VARCHAR(200) NOT NULL,
    COD_EXTERNO VARCHAR(10),
    DTH_CRIACAO DATE DEFAULT SYSDATE,
    COD_USUARIO_CRIACAO NUMBER(8) NOT NULL
        CONSTRAINT FK_USER_CRI_MOT_MOV
            REFERENCES USUARIO ( COD_USUARIO )
                NOT DEFERRABLE,
    IND_ATIVO NUMBER(1) NOT NULL
);

INSERT INTO MOTIVO_MOVIMENTACAO (COD_MOTIVO_MOVIMENTACAO, DSC_MOTIVO_MOVIMENTACAO, COD_USUARIO_CRIACAO, IND_ATIVO)
VALUES (1, 'MOVIMENTAÇÃO PADRÃO', (SELECT MIN(COD_FILIAL) FROM FILIAL WHERE IND_ATIVO = 'S') , 1);

CREATE SEQUENCE SQ_MOTIVO_MOVIMENTACAO_01
    INCREMENT BY 1
    START WITH 2
    MAXVALUE 99999999
    MINVALUE 1
    NOCYCLE
    NOCACHE
    NOORDER;

ALTER TABLE HISTORICO_ESTOQUE ADD (
    OBS_USUARIO VARCHAR(200),
    COD_MOTIVO_MOVIMENTACAO NUMBER(8)
        CONSTRAINT FK_HIST_MOTIV_MOV
            REFERENCES MOTIVO_MOVIMENTACAO ( COD_MOTIVO_MOVIMENTACAO )
                NOT DEFERRABLE
    );

UPDATE HISTORICO_ESTOQUE SET COD_MOTIVO_MOVIMENTACAO = 1 WHERE COD_MOTIVO_MOVIMENTACAO IS NULL;

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, NOM_RECURSO, COD_RECURSO_PAI)
VALUES (SQ_RECURSO_01.NEXTVAL, 'Motivo Movimentacao', 'motivo-movimentacao', 0);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
(
    SQ_RECURSO_ACAO_01.NEXTVAL,
    (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'motivo-movimentacao'),
    (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
    'Listar Motivos de Movimentação'
);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
(
    SQ_RECURSO_ACAO_01.NEXTVAL,
    (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'motivo-movimentacao'),
    (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'),
    'Cadastrar Motivo de Movimentação'
);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
(
    SQ_RECURSO_ACAO_01.NEXTVAL,
    (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'motivo-movimentacao'),
    (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'),
    'Editar Motivo de Movimentação'
);

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
(
    SQ_RECURSO_ACAO_01.NEXTVAL,
    (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'motivo-movimentacao'),
    (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'),
    'Deletar Motivo de Movimentação'
);

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES
(
    SQ_MENU_ITEM_01.NEXTVAL,
    (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE DSC_RECURSO_ACAO = 'Listar Motivos de Movimentação'),
    (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'),
    'Motivos de Movimentação',
    (SELECT NUM_PESO FROM MENU_ITEM WHERE TRANSLATE (DSC_MENU_ITEM,
                                                     'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                                                     'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'Motivo de Corte'),
    '#',
    '_self',
    'S'
);