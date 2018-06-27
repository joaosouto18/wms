/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 15/09/2017
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','7-checkout-expedicao.sql');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) 
    VALUES (SQ_ACAO_01.NEXTVAL, 'Checkout Expedição', 'checkout-expedicao'); 
 
 INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES    (   SQ_RECURSO_ACAO_01.NEXTVAL,   
                (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index'), 
                (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'checkout-expedicao'),
                'Checkout Expedição'
            );
  
  INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO,DSC_URL,DSC_TARGET,SHOW)
  VALUES    (   SQ_MENU_ITEM_01.NEXTVAL, 
                (SELECT COD_RECURSO_ACAO FROM recurso_acao WHERE COD_RECURSO = 
                    (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:index') 
                AND COD_ACAO = 
                    (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'checkout-expedicao')),
                (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND MENU_ITEM.COD_PAI = 0),'Checkout Expedição',10,'#','_self','S');