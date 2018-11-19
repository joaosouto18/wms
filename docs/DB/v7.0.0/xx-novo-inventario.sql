/* 
 * SCRIPT PARA: Scripts do novo inventário
 * DATA DE CRIAÇÃO: 14/11/2018 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('xx-novo-inventario.sql', '') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7', 'xx-novo-inventario.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

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

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 