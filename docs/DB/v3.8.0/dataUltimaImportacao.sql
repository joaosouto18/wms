INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '3.8.0', 'dataUltimaImportacao.sql');
ALTER TABLE IMPORTACAO_ARQUIVO ADD (
    ULTIMA_IMPORTACAO DATE
);