INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '4.0.0','validadeInventario.sql');

ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO ADD (DTH_VALIDADE DATE NULL);