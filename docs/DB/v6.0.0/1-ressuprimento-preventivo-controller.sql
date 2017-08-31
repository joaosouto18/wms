/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 18/08/2017
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','1 ressuprimento-preventivo-controller.sql');

INSERT INTO RECURSO (DSC_RECURSO,COD_RECURSO,COD_RECURSO_PAI,NOM_RECURSO)
  VALUES ('Ressuprimento Preventivo',SQ_RECURSO_01.NEXTVAL,0,'expedicao:ressuprimento-preventivo');
  
  INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:ressuprimento-preventivo'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index'), 'Ressuprimento Preventivo');
  
  INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO,DSC_URL,DSC_TARGET,SHOW)
  VALUES (SQ_MENU_ITEM_01.NEXTVAL, (select COD_RECURSO_ACAO from recurso_acao where COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:ressuprimento-preventivo') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index')),
  (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Ressuprimento' AND MENU_ITEM.COD_PAI = 53),'Ressuprimento Preventivo',10,'#','_self','S');

ALTER TABLE ONDA_RESSUPRIMENTO
  ADD (TIPO_ONDA CHAR(1));