INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v7.xx.x', '03-integracao-observacao-pedido.sql');

alter table pedido
add (dsc_observacao long null);

alter table integracao_pedido
add (dsc_observacao_integracao long null);