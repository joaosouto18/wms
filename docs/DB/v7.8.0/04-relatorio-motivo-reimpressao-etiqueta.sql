/* 
 * SCRIPT PARA: Disponibilização de novo recuros nos perfis de usuários
 * DATA DE CRIAÇÃO: 14/08/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('04-relatorio-motivo-reimpressao-etiqueta.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
        VALUES (SYSDATE, '7.8', '04-relatorio-motivo-reimpressao-etiqueta.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
        INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.nextval, 'Relatório de Reimpressão de Etiquetas', 'relatorio-reimpressao-etiqueta');
        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
        VALUES (
                   SQ_RECURSO_ACAO_01.nextval,
                   (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index'),
                   (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'relatorio-reimpressao-etiqueta'),
                   'Relatório de reimpressão de etiquetas'
               );

        INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
        VALUES (
                   SQ_MENU_ITEM_01.nextval,
                   (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO
                    WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index')
                      AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'relatorio-reimpressao-etiqueta')),
                   (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Relatórios')),
                   'Motivo de reimpressão de etiqueta', 1, '#', '_self', 'S'
               );

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 