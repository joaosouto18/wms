INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.0.0','8-parametro-status-recebimento-enderecado.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'PARAMETROS DE INTEGRAÇÃO'), 'STATUS_RECEBIMENTO_ENDERECADO','Retornar ao ERP status de recebimento quando ENDEREÇADO','S','A', 'N');