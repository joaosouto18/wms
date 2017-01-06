-- NÂO DEVE RODAR ESTE SCRIPT
-- SCRIPT APENAS PARA EXEMPLOS E DESENVOLVIMENTO

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','exemplo-integracao.sql');

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO, DSC_CONEXAO_INTEGRACAO, SERVIDOR, PORTA, USUARIO, SENHA, DBNAME, PROVEDOR)
 VALUES (1,'INTEGRACAO DE PRODUTOS','192.168.16.6','1521','WMS_IMPERIUM','G086gvds','xe', 'ORACLE');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (1,1,'select p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (p.obs2,' || '''FL''' || ',' || '''N''' | |','|| '''S''' || ') as EMBALAGEM_ATIVA from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod = e.codprod and d.codepto = p.codepto and sec.codsec = p.codsec and f.codfornec = p.codfornec and e.codfilial = :codfilial and p.dtultaltcom >= :dtultaltcom order by p.codprod',600,'S',null);
