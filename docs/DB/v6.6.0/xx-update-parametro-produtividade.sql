INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'x.x.x','xx-update-parametro-produtividade.sql');

update parametro set DSC_TITULO_PARAMETRO = 'Número máximo de funcionários para apontamento de produtividade de mapa' where DSC_TITULO_PARAMETRO = 'Máximo produtividade mapa';