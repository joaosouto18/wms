INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','integracaoLinear.sql');

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO, DSC_CONEXAO_INTEGRACAO, SERVIDOR, PORTA, USUARIO, SENHA, DBNAME, PROVEDOR)
VALUES (1,'INTEGRACAO DE PRODUTOS','localhost','1521','wms_linhares','wms_adm','xe', 'ORACLE');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (1,1,
        'SELECT a.es1_cod AS COD_PRODUTO, ''UNICA'' AS DSC_GRADE, b.es1_desc AS DESCRICAO_PRODUTO, IF (b.es1_familia = 0, 9999 , b.es1_familia) AS CODIGO_CLASSE_NIVEL_1, f.tab_desc AS DSC_CLASSE_NIVEL_1, b.es1_departamento AS CODIGO_CLASSE_NIVEL_2, d.tab_desc AS DSC_CLASSE_NIVEL_2, b.es1_secao AS CODIGO_CLASSE_NIVEL_3, s.tab_desc AS DSC_CLASSE_NIVEL_3, b.es1_categoria AS CODIGO_CLASSE_NIVEL_4, g.tab_desc AS DSC_CLASSE_NIVEL_4, IF (a.cg2_cod = 0, 999999, a.cg2_cod) AS CODIGO_FABRICANTE, c.cg2_nome AS DESCRICAO_FABRICANTE, a.es1_um2 AS DESCRICAO_EMBALAGEM,  IF(a.es1_pesavel = 1,''S'',''N'') AS PESO_VARIAVEL, a.es1_qembc AS QTD_EMBALAGEM, b.es1_codbarra AS COD_BARRAS, w.es1_altura AS ALTURA_EMBALAGEM, w.es1_largura AS LARGURA_EMBALAGEM, w.es1_profundidade AS PROFUNDIDADE_EMBALAGEM, ''S'' AS EMBALAGEM_ATIVA, ''N'' as POSSUI_VALIDADE, NULL AS DIAS_VIDA_UTIL, 1 AS CUBAGEM_EMBALAGEM, 1 AS PESO_BRUTO_EMBALAGEM FROM es1 a INNER JOIN es1p b ON b.es1_cod = a.es1_cod LEFT JOIN st_familia f ON f.tab_cod = b.es1_familia LEFT JOIN st_departamento d ON d.tab_cod = b.es1_departamento LEFT JOIN st_secao s ON s.tab_cod = b.es1_secao LEFT JOIN st_categoria g ON g.tab_cod = b.es1_categoria LEFT JOIN es1w w ON w.es1_cod = a.es1_cod AND w.es1_empresa = a.es1_empresa LEFT JOIN cg2 c ON c.cg2_cod = a.cg2_cod WHERE a.es1_empresa = 1 and a.es1_dtalteracao > :dthExecucao',
        600,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (2,1,
        'select DATE_FORMAT(NOW(), ''%d/%m/%Y %H:%i:%s'') AS DTH, e.codprod as COD_PRODUTO, e.qtestger as ESTOQUE_GERENCIAL, sum(e.qtestger-e.qtreserv-e.qtbloqueada) as ESTOQUE_DISPONIVEL, trunc(sum(e.qtestger*e.custoultent),2) VALOR_ESTOQUE, trunc(e.custoultent,2) CUSTO_UNITARIO, p.qtunit FATOR_UNIDADE_VENDA, p.unidade DSC_UNIDADE from pcest e, pcprodut p where e.codprod=p.codprod and e.codfilial=:codFilial group by e.codprod,e.qtestger,e.custoultent,p.qtunit,p.unidade',
        601,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (3,1,
        'SELECT C.COD_CARGA_EXTERNO as CARGA, C.DSC_PLACA_CARGA as PLACA, P.COD_PEDIDO as PEDIDO, null as COD_PRACA, null as DSC_PRACA, IT.COD_ITINERARIO as COD_ROTA, IT.DSC_ITINERARIO as DSC_ROTA, C.COD_CLIENTE_EXTERNO as COD_CLIENTE, PES.NOM_PESSOA as NOME, NVL(PF.NUM_CPF,PJ.NUM_CNPJ) as CPF_CNPJ, PES.COD_TIPO_PESSOA as TIPO_PESSOA, PE.DSC_ENDERECO as LOGRADOURO, PE.NUM_ENDERECO as NUMERO, PE.NOM_BAIRRO as BAIRRO, PE.NOM_LOCALIDADE as CIDADE, UF.COD_REFERENCIA_SIGLA as UF, PE.DSC_COMPLEMENTO as COMPLEMENTO, PE.DSC_PONTO_REFERENCIA as REFERENCIA, PE.NUM_CEP as CEP, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, PP.QUANTIDADE as QTD, PP.VALOR_VENDA FROM CARGA C LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = P.COD_PEDIDO LEFT JOIN ITINERARIO IT ON IT.COD_ITINERARIO = P.COD_ITINERARIO LEFT JOIN CLIENTE C ON C.COD_PESSOA = P.COD_PESSOA LEFT JOIN PESSOA PES ON PES.COD_PESSOA = C.COD_PESSOA LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = PES.COD_PESSOA LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = PES.COD_PESSOA LEFT JOIN SIGLA UF ON UF.COD_SIGLA = PE.COD_UF LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO WHERE C.COD_CARGA = 7004 ORDER BY C.COD_CARGA, P.COD_PEDIDO',
        602,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (4,1,
        'SELECT C.COD_CARGA_EXTERNO as CARGA, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO',
        603,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (5,1,
        'SELECT C.COD_CARGA_EXTERNO as CARGA, P.COD_PEDIDO as PEDIDO, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO, P.COD_PEDIDO, PP.COD_PRODUTO, PP.DSC_GRADE',
        604,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
VALUES (6,1,
        'SELECT DATE_FORMAT(NOW(), ''%d/%m/%Y %H:%i:%s'') AS DTH, cmd.cg2_cod AS COD_FORNECEDOR, IF(cg2.cg2_tipopessoa = ''J'',cg2.cg2_cgc, cg2.cg2_cpf) AS CNPJ, IF(cg2.cg2_tipopessoa = ''J'',cg2.cg2_inscestad, cg2.cg2_rg) AS INSCRICAO_ESTADUAL, cg2.cg2_nome AS NOME_FORNECEDOR, cmd.cmd_num AS NUMERO_NF, cmd.cmd_serie AS SERIE_NF, cmd.cmd_emissao AS DATA_EMISSAO_NF, cmd.cmd_placa AS PLACA_NF, cma.es1_cod AS COD_PRODUTO, cma.cma_quant AS QTD, cma.cma_vtotal AS VLR, cmd.cmd_dtent FROM sglinx.CMD INNER JOIN sglinx.cma ON cma.cmd_num = cmd.cmd_num AND cma.cmd_serie = cmd.cmd_serie AND cma.cg2_cod = cmd.cg2_cod AND cma.cmd_empresa = cmd.cmd_empresa AND cma.cmd_modelo = cmd.cmd_modelo AND cma.cmd_emissao = cmd.cmd_emissao INNER JOIN sglinx.cg2 ON cg2.cg2_cod = cmd.cg2_cod WHERE  cmd.cmd_dtent >= :dthExecucao',
        605,'S',null);
