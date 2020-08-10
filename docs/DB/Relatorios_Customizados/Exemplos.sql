/*
 * RELATÓRIOS DE PRODUTOS - Simples listagem de produtos
 */

/*
Insert into RELATORIO_CUSTOMIZADO (COD_RELATORIO_CUSTOMIZADO,DSC_TITULO_RELATORIO,DSC_QUERY,COD_CONEXAO_INTEGRACAO,IND_ALLOW_XLS,IND_ALLOW_PDF,IND_ALLOW_SEARCH,DTH_INATIVACAO,DSC_GRUPO_RELATORIO)
values ('1','Relatório de Produtos',
'SELECT COD_PRODUTO as Codigo, DSC_PRODUTO as Descrição
   FROM PRODUTO P
  WHERE 1 = 1  :CodProduto :DscProduto',null,'S','S','S',null,'RELATÓRIOS CADASTRAIS');

Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('1','1','CodProduto','Código','N','text',null,null,' AND P.COD_PRODUTO = '':value'' ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('2','1','DscProduto','Descrição','N','text',null,'30',' AND P.DSC_PRODUTO LIKE ''%:value%'' ');

Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('1','1','P.COD_PRODUTO ASC','Código ASC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('2','1','P.DSC_PRODUTO ASC','Descrição ASC');

/*
 * RELATÓRIO DE EXPEDIÇÃO - Listagem de Expedições, para demonstrar as possibilidades de filtros
 */
Insert into RELATORIO_CUSTOMIZADO (COD_RELATORIO_CUSTOMIZADO,DSC_TITULO_RELATORIO,DSC_QUERY,COD_CONEXAO_INTEGRACAO,IND_ALLOW_XLS,IND_ALLOW_PDF,IND_ALLOW_SEARCH,DTH_INATIVACAO,DSC_GRUPO_RELATORIO)
values ('2','Relatório de Expedição',
'SELECT E.COD_EXPEDICAO as EXPEDICAO,
        E.DSC_PLACA_EXPEDICAO as PLACA,
        TO_CHAR(E.DTH_INICIO,''DD/MM/YYYY'') as DTH_INICIO,
        TO_CHAR(E.DTH_FINALIZACAO,''DD/MM/YYYY'') as DTH_FINAL,
        S.DSC_SIGLA as SITUACAO
   FROM EXPEDICAO E
   LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
  WHERE E.DTH_INICIO >= TO_DATE(''10-06-2020 00:00'',''DD-MM-YYYY HH24:MI'')
        :CodExpedicao :DthInicio :Situacao :Finalizado',null,'S','S','S',null,'RELATÓRIOS DE EXPEDIÇÃO');

Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('3','2','CodExpedicao','Expedição','N','text',null,'8',' AND E.COD_EXPEDICAO = '':value'' ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('4','2','DthInicio','Data de Inicio','N','date',null,null,' AND E.DTH_INICIO >= TO_DATE('':value 00:00'',''DD/MM/YYYY HH24:MI'') ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('5','2','Situacao','Situação','N','SQL','SELECT COD_SIGLA as VALUE, DSC_SIGLA as LABEL FROM SIGLA WHERE COD_TIPO_SIGLA = 53',null,' AND E.COD_STATUS = '':value'' ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('6','2','Finalizado','Finalizado','N','select','{"E.COD_STATUS = 465":"Sim","E.COD_STATUS <> 465":"Nao"}',null,' AND :value ');

Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('7','2','E.DTH_FINALIZACAO ASC','Dt. Finalização ASC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('4','2','E.COD_EXPEDICAO DESC','Código DSC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('3','2','E.COD_EXPEDICAO ASC','Código ASC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('5','2','E.DTH_INICIO ASC','Dt. Inicio ASC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('6','2','E.DTH_INICIO DESC','Dt. Inicio DSC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('8','2','E.DTH_FINALIZACAO DESC','Dt. Finalização DSC');

/*
 * RELATÓRIO DE SAIDA DE PRODUTOS - Relatório Solicitado pela SonoShow
 */
Insert into RELATORIO_CUSTOMIZADO (COD_RELATORIO_CUSTOMIZADO,DSC_TITULO_RELATORIO,DSC_QUERY,COD_CONEXAO_INTEGRACAO,IND_ALLOW_XLS,IND_ALLOW_PDF,IND_ALLOW_SEARCH,DTH_INATIVACAO,DSC_GRUPO_RELATORIO)
values ('3','Saída de Produtos',
'SELECT TO_CHAR(E.DTH_FINALIZACAO,''DD/MM/YYYY HH24:MI:SS'') as DTH_SAIDA,
        E.COD_EXPEDICAO,
        C.COD_CARGA_EXTERNO as CARGA,
        P.COD_EXTERNO as COD_PEDIDO,
        cli.cod_cliente_externo as COD_CLIENTE,
        PES.NOM_PESSOA as CLIENTE,
        PP.COD_PRODUTO,
        PP.DSC_GRADE,
        PROD.DSC_PRODUTO,
        pp.quantidade as QTD_PEDIDO,
        CASE WHEN E.COD_STATUS = 465 THEN
        PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0) ELSE 0 END as QTD_ATENDIDA,
        S.DSC_SIGLA as STATUS_EXPEDICAO
   FROM PEDIDO P
   LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
   LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
   LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
   LEFT JOIN CLIENTE CLI ON cli.cod_pessoa = P.COD_PESSOA
   LEFT JOIN PESSOA PES ON PES.COD_PESSOA = P.COD_PESSOA
   LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO
                         AND PROD.DSC_GRADE = PP.DSC_GRADE
   LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
  WHERE 1 = 1 :CodProduto :DthFinal1 :DthFinal2',null,'S','S','S',null,'RELATÓRIOS DE EXPEDIÇÃO');

Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('7','3','CodProduto','Produto','N','text',null,null,' AND PP.COD_PRODUTO = '':value'' ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('8','3','DthFinal1','Dt. Finalização Inicial','N','date',null,null,' AND E.DTH_FINALIZACAO >= TO_DATE('':value 00:00'',''DD/MM/YYYY HH24:MI'') ');
Insert into RELATORIO_CUSTOMIZADO_FILTRO (COD_RELATORIO_CUST_FILTRO,COD_RELATORIO_CUSTOMIZADO,NOME_PARAM,DSC_TITULO,IND_OBRIGATORIO,TIPO,PARAMS,TAMANHO,DSC_QUERY) values ('9','3','DthFinal2','Dt. Finalização Final','N','date',null,null,' AND E.DTH_FINALIZACAO <= TO_DATE('':value 00:00'',''DD/MM/YYYY HH24:MI'') ');

Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('9','3','E.DTH_FINALIZACAO ASC','Dt. Finalização ASC');
Insert into RELATORIO_CUSTOMIZADO_SORT (COD_RELATORIO_CUST_SORT,COD_RELATORIO_CUSTOMIZADO,DSC_QUERY,DSC_TITULO) values ('10','3','E.DTH_FINALIZACAO DESC','Dt. Finalização DSC');


/*
 * Permissões de acesso aos relatórios
 */

INSERT INTO RELATORIO_CUST_PERFIL_USUARIO ( COD_PERFIL_USUARIO, COD_RELATORIO_CUSTOMIZADO)
SELECT COD_PERFIL_USUARIO, COD_RELATORIO_CUSTOMIZADO FROM PERFIL_USUARIO, RELATORIO_CUSTOMIZADO


 */