/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Tarcísio César
 * Created: 10/08/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','33-historico-de-saldo.sql');

ALTER TABLE HISTORICO_ESTOQUE
  ADD (SALDO_ANTERIOR NUMBER(13,3) NULL);

ALTER TABLE HISTORICO_ESTOQUE
  ADD (SALDO_FINAL NUMBER(13,3) NULL);