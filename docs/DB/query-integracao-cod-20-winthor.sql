INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'x.x.x', 'query-integracao-cod-20-winthor.sql');


update acao_integracao_filtro set dsc_filtro = ' AND (nf.numnota IN (:?1) OR c.numcar IN (:?2)) ' where cod_acao_integracao = 20 and cod_tipo_registro = 612;