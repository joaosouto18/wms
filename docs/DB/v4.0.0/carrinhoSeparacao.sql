
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v4.0.0', 'carrinhoSeparacao.sql');

insert into parametro (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, dsc_parametro, DSC_titulo_parametro, ind_parametro_sistema, cod_tipo_atributo, dsc_valor_parametro)
values (sq_parametro_01.nextval,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'),'IND_QTD_CAIXA_PC','Quantidade de caixas por carrinho na separação PC','S','A',12);

insert into parametro (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, dsc_parametro, DSC_titulo_parametro, ind_parametro_sistema, cod_tipo_atributo, dsc_valor_parametro)
values (sq_parametro_01.nextval,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'),'IND_MODELO_SEPARACAO_PC','Modelo de separação é por Carrinho','S','A','S');

