/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 19/09/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','xx-cadastro-peso-embalagem.sql');

ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_ALTURA NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM  
  ADD NUM_CUBAGEM NUMBER(16,4) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_PROFUNDIDADE NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_LARGURA NUMBER(15,3) DEFAULT 0 NULL;
ALTER TABLE PRODUTO_EMBALAGEM 
  ADD NUM_PESO NUMBER(15,3) DEFAULT 0 NULL;