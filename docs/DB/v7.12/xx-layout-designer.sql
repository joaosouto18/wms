/* 
 * SCRIPT PARA: Scripts para inicialização do NictusFramework
 * DATA DE CRIAÇÃO: 02/01/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('xx-layout-designer.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.11', 'xx-layout-designer.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
        INSERT INTO RECURSO ( COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
        VALUES (SQ_RECURSO_01.NEXTVAL, 'Layout Designer', null, 'layout-designer');

        INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
        VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
                 (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'layout-designer'),
                 (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
                 'Lista de layouts'
               );

        INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
        VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
                 (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'layout-designer'),
                 (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'edit'),
                 'Editar'
               );

        INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
        VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
                 (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'layout-designer'),
                 (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'add'),
                 'Criar'
               );

        INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
        VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
                 (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'layout-designer'),
                 (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'delete'),
                 'Excluir'
               );

        INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
        VALUES (SQ_MENU_ITEM_01.NEXTVAL,
                (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'layout-designer') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
                (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Sistemas' AND COD_PAI = 0),
                'Designer de Layouts',
                (SELECT MAX(NUM_PESO) + 1 FROM MENU_ITEM WHERE COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Sistemas' AND COD_PAI = 0)),
                '#','_self','S');

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 SELECT * FROM ACAO;