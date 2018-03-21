INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.14','xx-cadastro-motorista.sql');

ALTER TABLE CARGA ADD NOM_MOTORISTA VARCHAR2(64 BYTE) NULL;
UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT ID, CARGA, PLACA, PEDIDO, TIPO_PEDIDO, COD_PRACA, DSC_PRACA, COD_ROTA, DSC_ROTA, COD_CLIENTE, NOME, CPF_CNPJ, TIPO_PESSOA, LOGRADOURO, NUMERO, BAIRRO, CIDADE, UF, COMPLEMENTO, REFERENCIA, CEP, PRODUTO, GRADE, QTD, VLR_VENDA, IND_PROCESSADO, SEQUENCIA_ENTREGA, NOM_MOTORISTA FROM TR_PEDIDO WHERE 1 = 1 AND (IND_PROCESSADO = ''N'' OR IND_PROCESSADO IS NULL) ORDER BY PEDIDO, PRODUTO' WHERE TABELA_REFERENCIA = 'TR_PEDIDO';
