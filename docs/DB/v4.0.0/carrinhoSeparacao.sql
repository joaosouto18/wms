INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v4.0.0', 'carrinhoSeparacao.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'),'IND_QTD_CAIXA_PC','Quantidade de caixas por carrinho na separação PC','S','A',12);

ALTER TABLE MODELO_SEPARACAO ADD (IND_SEPARACAO_PC VARCHAR(1) DEFAULT 'N');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'), 'CUBAGEM_CAIXA_CARRINHO', 'Cubagem da Caixa do Carrinho PC', 'S', 'A', '1');

ALTER TABLE MAPA_SEPARACAO_PRODUTO
  ADD (NUM_CAIXA_PC_INI NUMBER(8) NULL,
       NUM_CAIXA_PC_FIM NUMBER(8) NULL);

ALTER TABLE MAPA_SEPARACAO_QUEBRA
  MODIFY (IND_TIPO_QUEBRA CHAR(2 BYTE));