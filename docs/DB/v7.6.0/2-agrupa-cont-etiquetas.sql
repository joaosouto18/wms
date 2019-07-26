/* 
 * SCRIPT PARA: Criar parâmetros e configurações do agrupamento da contagem de etiquetas de separação e embalados
 * DATA DE CRIAÇÃO: 28/06/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('2-agrupa-cont-etiquetas.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.6', '2-agrupa-cont-etiquetas.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (AGRUP_CONT_ETIQUETAS CHAR(1))';

EXECUTE IMMEDIATE 'CREATE TABLE CAIXA_EMBALADO
(
    COD_CAIXA NUMBER(11) NOT NULL,
    DSC_CAIXA VARCHAR2(100) NOT NULL,
    PESO_MAX NUMBER(13,3) NOT NULL,
    CUBAGEM_MAX NUMBER(13,3) NOT NULL,
    MIX_MAX NUMBER(13,3) NOT NULL,
    UNIDADES_MAX NUMBER(13,3) NOT NULL,
    IS_ATIVA NUMBER(1) NOT NULL,
    IS_DEFAULT NUMBER(1) NOT NULL
)';

EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_CAIXA_EMB_01
    INCREMENT BY 1
    START WITH 1
    MAXVALUE 999999999999999999999999999
    MINVALUE 0
    NOCYCLE
    NOCACHE
    NOORDER';

EXECUTE IMMEDIATE 'ALTER TABLE CAIXA_EMBALADO ADD CONSTRAINT PK_CAIXA_EMBALADO PRIMARY KEY (COD_CAIXA)';

EXECUTE IMMEDIATE 'ALTER TABLE EXPEDICAO ADD (COUNT_VOLUMES NUMBER(8))';

EXECUTE IMMEDIATE 'ALTER TABLE ETIQUETA_SEPARACAO ADD (POS_VOLUME NUMBER(8))';
EXECUTE IMMEDIATE 'ALTER TABLE MAPA_SEPARACAO_EMB_CLIENTE ADD (POS_VOLUME NUMBER(8))';

        INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, NOM_RECURSO, COD_RECURSO_PAI)
        VALUES (SQ_RECURSO_01.NEXTVAL, 'Caixa Embalado', 'caixa-embalado', 0);

        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
        (
            SQ_RECURSO_ACAO_01.NEXTVAL,
            (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'caixa-embalado'),
            (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
            'Listar Caixas de Embalado'
        );

        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
        (
            SQ_RECURSO_ACAO_01.NEXTVAL,
            (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'caixa-embalado'),
            (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'),
            'Cadastrar Caixa de Embalado'
        );

        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
        (
            SQ_RECURSO_ACAO_01.NEXTVAL,
            (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'caixa-embalado'),
            (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'),
            'Editar Caixa de Embalado'
        );

        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
        (
            SQ_RECURSO_ACAO_01.NEXTVAL,
            (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'caixa-embalado'),
            (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'),
            'Deletar Caixa de Embalado'
        );

        INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES
        (
            SQ_ACAO_01.NEXTVAL,
            'Definir padrão',
            'set-default'
        );

        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES
        (
            SQ_RECURSO_ACAO_01.NEXTVAL,
            (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'caixa-embalado'),
            (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'set-default'),
            'Definir caixa embalado padrão'
        );

        INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW) VALUES
        (
            SQ_MENU_ITEM_01.NEXTVAL,
            (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE DSC_RECURSO_ACAO = 'Listar Caixas de Embalado'),
            (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Cadastros'),
            'Caixas de Embalados',
            (SELECT NUM_PESO FROM MENU_ITEM WHERE TRANSLATE (DSC_MENU_ITEM,
                                                             'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                                                             'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'Volume Patrimonio'),
            '#',
            '_self',
            'S'
        );


/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 