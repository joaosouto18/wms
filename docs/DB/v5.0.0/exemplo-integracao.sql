/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','exemplo-integracao.sql');

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO, DSC_CONEXAO_INTEGRACAO, SERVIDOR, PORTA, USUARIO, SENHA, DBNAME, PROVEDOR)
 VALUES (1,'INTEGRACAO DE PRODUTOS','192.168.16.6','1521','WMS_IMPERIUM','G086gvds','xe', 'ORACLE');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (1,1,'SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod=e.codprod AND d.codepto=p.codepto AND sec.codsec=p.codsec AND f.codfornec=p.codfornec AND e.codfilial=:codFilial AND log.codprod=p.codprod AND log.codrotina in(' || '''2014''' || ',' || '''292''' || ') AND (log.datainicio >= :dthExecucao OR p.dtultaltcom >= :dthExecucao) UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod=e.codprod and d.codepto=p.codepto and sec.codsec=p.codsec and f.codfornec=p.codfornec and p.dtcadastro>=:dthExecucao and e.codfilial=:codFilial',600,'S',null);

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (2,1,'select e.codprod as COD_PRODUTO, e.qtestger as ESTOQUE_GERENCIAL, sum(e.qtestger-e.qtreserv-e.qtbloqueada) as ESTOQUE_DISPONIVEL, trunc(sum(e.qtestger*e.custoultent),2) VALOR_ESTOQUE, trunc(e.custoultent,2) CUSTO_UNITARIO, p.qtunit FATOR_UNIDADE_VENDA, p.unidade DSC_UNIDADE from pcest e, pcprodut p where e.codprod=p.codprod and e.codfilial=:codFilial group by e.codprod,e.qtestger,e.custoultent,p.qtunit,p.unidade',601,'S',null);
