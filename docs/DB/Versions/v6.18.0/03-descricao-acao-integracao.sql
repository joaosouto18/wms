INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.18.0','03-descricao-acao-integracao');

ALTER TABLE ACAO_INTEGRACAO ADD (DSC_ACAO_INTEGRACAO VARCHAR2(64 BYTE));