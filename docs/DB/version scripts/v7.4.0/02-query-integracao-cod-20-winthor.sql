INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.4.0', '02-query-integracao-cod-20-winthor.sql');

/*
RODAR APENAS NA WILSO, CDC, ROVER E PLANETA

update acao_integracao_filtro set dsc_filtro = ' AND (nf.numnota IN (:?1) OR c.numcar IN (:?2)) ' where cod_acao_integracao = 20 and cod_tipo_registro = 612;
 */
