/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 08/08/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','32-corte-automatico-andamento-exp.sql');

ALTER TABLE PEDIDO_PRODUTO
  ADD (QTD_CORTADO_AUTOMATICO NUMBER(13,3) NULL);