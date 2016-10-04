INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v4.0.0', 'carrinhoSeparacao.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'),'IND_QTD_CAIXA_PC','Quantidade de caixas por carrinho na separação PC','S','A',12);

ALTER TABLE MODELO_SEPARACAO ADD (IND_SEPARACAO_PC VARCHAR(1) DEFAULT 'N');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'EXPEDICAO'), 'CUBAGEM_CAIXA_CARRINHO', 'Cubagem da Caixa do Carrinho PC', 'S', 'A', '1');

ALTER TABLE MAPA_SEPARACAO_PRODUTO
  ADD (NUM_CAIXA_PC_INI NUMBER(8) NULL,
       NUM_CAIXA_PC_FIM NUMBER(8) NULL);

ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD (CUBAGEM_TOTAL NUMBER(8,4));
ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD (NUM_CARRINHO NUMBER(8,0) NULL);


--CASO TENHA ALTERADO IND_TIPO_QUEBRA NA TABELA MAPA_SEPARACAO_QUEBRA PARA CHAR(2 BYTE)
ALTER TABLE mapa_separacao_quebra ADD ind_tipo_quebra_temp char(1);
UPDATE mapa_separacao_quebra SET ind_tipo_quebra_temp = TRIM(ind_tipo_quebra);
ALTER TABLE MAPA_SEPARACAO_QUEBRA DROP COLUMN IND_TIPO_QUEBRA;
ALTER TABLE MAPA_SEPARACAO_QUEBRA ADD IND_TIPO_QUEBRA CHAR(1);
UPDATE mapa_separacao_quebra SET ind_tipo_quebra = ind_tipo_quebra_temp;
ALTER TABLE mapa_separacao_quebra DROP COLUMN ind_tipo_quebra_temp;


ALTER TABLE RELATORIO_PICKING ADD (DSC_GRADE CHAR(45) NULL);

alter table mapa_separacao_produto add (IND_DIVERGENCIA VARCHAR(1) null);