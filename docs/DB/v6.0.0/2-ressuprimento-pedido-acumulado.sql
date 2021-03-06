/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 06/09/2017
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','2-ressuprimento-pedido-acumulado.sql');

 CREATE TABLE PEDIDO_ACUMULADO
  (
    COD_PEDIDO_ACUMULADO NUMBER (11) NOT NULL ,
    COD_PRODUTO       NUMBER (11) NOT NULL ,
    DSC_GRADE  VARCHAR2(10 BYTE)  NOT NULL,
    QTD_VENDIDA NUMBER (11) NOT NULL
  );
  
  CREATE SEQUENCE  SQ_PEDIDO_ACUMULADO  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;

INSERT INTO RECURSO (DSC_RECURSO,COD_RECURSO,COD_RECURSO_PAI,NOM_RECURSO)
  VALUES ('Pedido Acumulado',SQ_RECURSO_01.NEXTVAL,0,'expedicao:pedido-acumulado');
  
  INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:pedido-acumulado'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index'), 'Pedido Acumulado');
  
  INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO,DSC_URL,DSC_TARGET,SHOW)
  VALUES (SQ_MENU_ITEM_01.NEXTVAL, (select COD_RECURSO_ACAO from recurso_acao where COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:pedido-acumulado') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'index')),
  (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Ressuprimento' AND MENU_ITEM.COD_PAI = 53),'Pedido Acumulado',10,'#','_self','S');




