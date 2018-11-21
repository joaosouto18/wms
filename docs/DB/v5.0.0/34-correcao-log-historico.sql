/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Tarcísio César
 * Created: 10/08/2017
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','34-correcao-log-historico-estoque.sql');

UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'E' WHERE OBSERVACAO LIKE 'Mov. ref. endereçamento do Palete%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'R' WHERE OBSERVACAO LIKE 'Mov. ref. ressuprimento preventivo coletor%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'R' WHERE OBSERVACAO LIKE 'Mov. ref. onda%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'S' WHERE OBSERVACAO LIKE 'Mov. ref. expedicao%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'I' WHERE OBSERVACAO LIKE 'Mov. correção inventário%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'T' WHERE OBSERVACAO LIKE 'Transferencia de Estoque%';
UPDATE HISTORICO_ESTOQUE SET IND_TIPO = 'M' WHERE OBSERVACAO LIKE 'Movimentação manual%';