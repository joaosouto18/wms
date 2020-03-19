INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.12', '08-correcao-tipo-integracao.sql');

--usar este update apenas para os clientes que usam WINTHOR
--UPDATE ACAO_INTEGRACAO SET COD_TIPO_ACAO_INTEGRACAO = 621 WHERE cod_tipo_acao_integracao = 618 AND (LOWER(DSC_QUERY) LIKE '%update%dtfimcheckout =%' OR LOWER(DSC_QUERY) LIKE '%update%dtinicialcheckout =%');