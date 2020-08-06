
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'x.xx', 'xx-comparativo-inventario-ERP.sql');

INSERT INTO RECURSO (DSC_RECURSO, COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO)
     VALUES ('Comparativo de Inventários', SQ_RECURSO_01.NEXTVAL,0,'inventario_novo:comparativo-inventario');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
     VALUES (SQ_RECURSO_ACAO_01.NEXTVAL,
             (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:comparativo-inventario'),
             (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
             'Comparar inventários WMS x ERP');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
     VALUES (SQ_MENU_ITEM_01.NEXTVAL,
             (SELECT COD_RECURSO_ACAO
                FROM RECURSO_ACAO
               WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'inventario_novo:comparativo-inventario')
                 AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
            (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Novo Inventário'), 'Comparativo Inventário WMS x ERP', 12, '#','_selft', 'S');

UPDATE MENU_ITEM
  SET DSC_MENU_ITEM = 'Comparativo Estoque WMS x ERP',
      COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Novo Inventário')
 WHERE DSC_MENU_ITEM = 'Comparativo Estoque ERP x WMS';

 UPDATE MENU_ITEM
    SET SHOW = 'N'
  WHERE DSC_MENU_ITEM = 'Inventário'
    AND COD_PAI = 0;

 UPDATE MENU_ITEM
    SET DSC_MENU_ITEM = 'Inventário'
  WHERE DSC_MENU_ITEM = 'Novo Inventário'
    AND COD_PAI = 0;
