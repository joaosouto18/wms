/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','exemplo-integracao.sql');

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO, DSC_CONEXAO_INTEGRACAO, SERVIDOR, PORTA, USUARIO, SENHA, DBNAME, PROVEDOR)
 VALUES (1,'INTEGRACAO DE PRODUTOS','localhost','1521','wms_linhares','wms_adm','xe', 'ORACLE');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (1,1,'SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod=e.codprod AND d.codepto=p.codepto AND sec.codsec=p.codsec AND f.codfornec=p.codfornec AND e.codfilial=:codFilial AND log.codprod=p.codprod AND log.codrotina in(' || '''2014''' || ',' || '''292''' || ') AND (log.datainicio >= :dthExecucao OR p.dtultaltcom >= :dthExecucao) UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod=e.codprod and d.codepto=p.codepto and sec.codsec=p.codsec and f.codfornec=p.codfornec and p.dtcadastro>=:dthExecucao and e.codfilial=:codFilial',600,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (2,1,'select e.codprod as COD_PRODUTO, e.qtestger as ESTOQUE_GERENCIAL, sum(e.qtestger-e.qtreserv-e.qtbloqueada) as ESTOQUE_DISPONIVEL, trunc(sum(e.qtestger*e.custoultent),2) VALOR_ESTOQUE, trunc(e.custoultent,2) CUSTO_UNITARIO, p.qtunit FATOR_UNIDADE_VENDA, p.unidade DSC_UNIDADE from pcest e, pcprodut p where e.codprod=p.codprod and e.codfilial=:codFilial group by e.codprod,e.qtestger,e.custoultent,p.qtunit,p.unidade',601,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (3,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, C.DSC_PLACA_CARGA as PLACA, P.COD_PEDIDO as PEDIDO, null as COD_PRACA, null as DSC_PRACA, IT.COD_ITINERARIO as COD_ROTA, IT.DSC_ITINERARIO as DSC_ROTA, C.COD_CLIENTE_EXTERNO as COD_CLIENTE, PES.NOM_PESSOA as NOME, NVL(PF.NUM_CPF,PJ.NUM_CNPJ) as CPF_CNPJ, PES.COD_TIPO_PESSOA as TIPO_PESSOA, PE.DSC_ENDERECO as LOGRADOURO, PE.NUM_ENDERECO as NUMERO, PE.NOM_BAIRRO as BAIRRO, PE.NOM_LOCALIDADE as CIDADE, UF.COD_REFERENCIA_SIGLA as UF, PE.DSC_COMPLEMENTO as COMPLEMENTO, PE.DSC_PONTO_REFERENCIA as REFERENCIA, PE.NUM_CEP as CEP, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, PP.QUANTIDADE as QTD, PP.VALOR_VENDA FROM CARGA C LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = P.COD_PEDIDO LEFT JOIN ITINERARIO IT ON IT.COD_ITINERARIO = P.COD_ITINERARIO LEFT JOIN CLIENTE C ON C.COD_PESSOA = P.COD_PESSOA LEFT JOIN PESSOA PES ON PES.COD_PESSOA = C.COD_PESSOA LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = PES.COD_PESSOA LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = PES.COD_PESSOA LEFT JOIN SIGLA UF ON UF.COD_SIGLA = PE.COD_UF LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO WHERE C.COD_CARGA = 7004 ORDER BY C.COD_CARGA, P.COD_PEDIDO',602,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (4,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO',603,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (5,1,'SELECT C.COD_CARGA_EXTERNO as CARGA, P.COD_PEDIDO as PEDIDO, PP.COD_PRODUTO as PRODUTO, PP.DSC_GRADE as GRADE, SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD FROM PEDIDO_PRODUTO PP LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA WHERE C.COD_CARGA_EXTERNO = :?1 GROUP BY COD_CARGA_EXTERNO, P.COD_PEDIDO, PP.COD_PRODUTO, PP.DSC_GRADE',604,'S',null);

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'PARÂMETROS DE INTEGRAÇÃO'),
  'INTEGRACAO_CODIGO_BARRAS_BANCO', 'integracao Banco de Dados', 'S', 'A', 'S');

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT TO_CHAR(MAX(DTH),' || '''DD/MM/YYYY HH24:MI:SS''' || ') as DTH,
        COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA FROM (SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA, log.datainicio AS DTH FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod=e.codprod AND d.codepto=p.codepto AND sec.codsec=p.codsec AND f.codfornec=p.codfornec AND e.codfilial=:codFilial AND log.codprod=p.codprod AND log.codrotina in(' || '''2014''' || ',' || '''292''' || ') AND (log.datainicio > :dthExecucao OR p.dtultaltcom > :dthExecucao) UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA, p.dtcadastro AS DTH from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod=e.codprod and d.codepto=p.codepto and sec.codsec=p.codsec and f.codfornec=p.codfornec and p.dtcadastro > :dthExecucao and e.codfilial=:codFilial )
 GROUP BY COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA
' WHERE COD_ACAO_INTEGRACAO = 1;

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT c.numcar CARGA, v.placa PLACA, c.numped PEDIDO, c.codpraca COD_PRACA, pr.praca DSC_PRACA, pr.rota COD_ROTA, rota.descricao DSC_ROTA, c.codcli COD_CLIENTE, cli.cliente NOME, cli.cgcent CPF_CNPJ, cli.tipofj TIPO_PESSOA, cli.enderent LOGRADOURO, cli.numeroent NUMERO, cli.bairroent BAIRRO, cli.municent CIDADE, cli.estent UF, cli.complementoent COMPLEMENTO, cli.pontorefer REFERENCIA, cli.cepent CEP, i.codprod PRODUTO, ''UNICA'' GRADE, i.qt QTD, SUM(i.qt*i.pvenda) VLR_VENDA, g.datamon ||' || ''' ''' || '||g.horamon||' || ''':''' || '||g.minutomon AS DTH FROM pcpedc c, pcpedi i, pcpraca pr, pcrotaexp rota, pcclient cli, pccarreg g, pcveicul v WHERE c.numped=i.numped AND c.codcli=cli.codcli AND pr.codpraca=c.codpraca AND pr.rota=rota.codrota AND c.numcar=g.numcar AND g.codveiculo=v.codveiculo AND c.posicao NOT IN (' || '''C''' ||') AND TO_DATE(g.datamon||' || ''' ''' || '||g.horamon||' || ''':''' || '||g.minutomon||' || ''':00''' ||',' || '''DD/MM/YY HH24:MI:SS''' || ') >  :dthExecucao AND c.codfilial in ( :codFilial ) GROUP BY c.numcar, v.placa, c.numped, c.codpraca, pr.praca, pr.rota, rota.descricao, c.codcli, cli.cliente, cli.cgcent, cli.tipofj, cli.enderent, cli.numeroent, cli.bairroent, cli.municent, cli.estent, cli.complementoent, cli.pontorefer, cli.cepent, i.codprod, i.qt, i.numseq, c.data,c.hora,c.minuto ORDER BY i.numseq' WHERE COD_TIPO_ACAO_INTEGRACAO = 602;
