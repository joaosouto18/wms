INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO, DSC_CONEXAO_INTEGRACAO, SERVIDOR, PORTA, USUARIO, SENHA, DBNAME, PROVEDOR)
 VALUES (1,'INTEGRACAO DE PRODUTOS','localhost','1521','wms_linhares','wms_adm','xe', 'ORACLE');

/* Integrações de Resumo da Conferencia*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (1,1,'SELECT * FROM PRODUTO',600,'S',null);

/* Integrações de Envio do Estoque*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (2,1,'SELECT E.COD_PRODUTO, SUM(E.QTD) as ESTOQUE_GERENCIAL, SUM(E.QTD) as ESTOQUE_DISPONIVEL, SUM(E.QTD) as VALOR_ESTOQUE, 1 as CUSTO_UNITARIO, 1 as UNIDADE_VENDA, ' || '''FD''' ||' as DSC_UNIDADE FROM ESTOQUE E GROUP BY COD_PRODUTO',601,'S',null);

/* Integrações de Envio de Pedidos*/
 INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (3,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, C.DSC_PLACA_CARGA as PLACA, P.COD_PEDIDO as PEDIDO, null as COD_PRACA, null as DSC_PRACA, IT.COD_ITINERARIO as COD_ROTA, IT.DSC_ITINERARIO as DSC_ROTA, C.COD_CLIENTE_EXTERNO as COD_CLIENTE, PES.NOM_PESSOA as NOME, NVL(PF.NUM_CPF,PJ.NUM_CNPJ) as CPF_CNPJ, PES.COD_TIPO_PESSOA as TIPO_PESSOA, PE.DSC_ENDERECO as LOGRADOURO, PE.NUM_ENDERECO as NUMERO, PE.NOM_BAIRRO as BAIRRO, PE.NOM_LOCALIDADE as CIDADE, UF.COD_REFERENCIA_SIGLA as UF, PE.DSC_COMPLEMENTO as COMPLEMENTO, PE.DSC_PONTO_REFERENCIA as REFERENCIA, PE.NUM_CEP as CEP, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, PP.QUANTIDADE as QTD, PP.VALOR_VENDA as VLR_VENDA FROM CARGA C LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = P.COD_PEDIDO LEFT JOIN ITINERARIO IT ON IT.COD_ITINERARIO = P.COD_ITINERARIO LEFT JOIN CLIENTE C ON C.COD_PESSOA = P.COD_PESSOA LEFT JOIN PESSOA PES ON PES.COD_PESSOA = C.COD_PESSOA LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = PES.COD_PESSOA LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = PES.COD_PESSOA LEFT JOIN SIGLA UF ON UF.COD_SIGLA = PE.COD_UF LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO WHERE C.COD_CARGA = 7004 ORDER BY C.COD_CARGA, P.COD_PEDIDO',602,'S',null);

/* Integrações de Resumo da Conferencia*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (4,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO',603,'S',null);

/* Integrações do Detalhamento da Conferencia*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (5,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, P.COD_PEDIDO as PEDIDO, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO, P.COD_PEDIDO, PP.COD_PRODUTO, PP.DSC_GRADE',604,'S',null);

/* Integrações de Nota Fiscal */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (6,2,'SELECT F.COD_EXTERNO COD_FORNECEDOR, NVL(PJ.NOM_FANTASIA,P.NOM_PESSOA) NOM_FORNECEDOR, NVL(PF.NUM_CPF,PJ.NUM_CNPJ) CPF_CNPJ, PROD.DSC_GRADE DSC_GRADE, PROD.COD_PRODUTO COD_PRODUTO, PJ.INSCRICAO_ESTADUAL INSCRICAO_ESTADUAL, NF.NUM_NOTA_FISCAL NUM_NOTA_FISCAL, NF.COD_SERIE_NOTA_FISCAL COD_SERIE_NOTA_FISCAL, NF.DAT_EMISSAO DAT_EMISSAO, NF.DSC_PLACA_VEICULO DSC_PLACA_VEICULO, NFI.QTD_ITEM QTD_ITEM, NULL VALOR_TOTAL, TO_CHAR(DTH_ENTRADA,'||'''DD/MM/YY HH24:MI:SS'''||') DTH FROM NOTA_FISCAL NF INNER JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL INNER JOIN FORNECEDOR F ON NF.COD_FORNECEDOR = F.COD_FORNECEDOR INNER JOIN PESSOA P ON P.COD_PESSOA = F.COD_FORNECEDOR LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = P.COD_PESSOA LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = P.COD_PESSOA INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = NFI.COD_PRODUTO AND PROD.DSC_GRADE = NFI.DSC_GRADE WHERE DTH_ENTRADA > :dthExecucao ORDER BY F.COD_EXTERNO',605,'S',TO_DATE('10/05/17 06:00:00','DD/MM/YY/HH24:MI:SS'));

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO,DSC_CONEXAO_INTEGRACAO,SERVIDOR,PORTA,USUARIO,SENHA,DBNAME,PROVEDOR)
  VALUES (2,'INTEGRACAO','localhost','1521','wms_abrafer','wms_adm','xe','ORACLE');
