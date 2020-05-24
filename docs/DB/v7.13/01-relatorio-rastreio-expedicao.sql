/* 
 * SCRIPT PARA: Registro de nova funcionalidade
 * DATA DE CRIAÇÃO: 08/05/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('01-relatorio-rastreio-expedicao.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.13', '01-relatorio-rastreio-expedicao.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.nextval, 'Rastreio de Expedição', 'rastreio');
        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
        VALUES (SQ_RECURSO_ACAO_01.nextval,
                (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_saida'),
                (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'rastreio'),
                'Relatório de Rastreio de Expedição'
               );

        INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
        VALUES (SQ_MENU_ITEM_01.nextval,
                (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE DSC_RECURSO_ACAO = 'Relatório de Rastreio de Expedição'),
                (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI IN (
                    SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios'
                    )),
                'Rastreio de Expedição',
                8,
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
