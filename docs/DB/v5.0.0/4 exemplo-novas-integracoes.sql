/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','exemplo-novas-integracoes.sql');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (605, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'INTEGRACAO'),'NOTAS FISCAIS', 'NF');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (6,1,'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, ' || '''UNICA''' || ' DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, ' || '''DD/MM/YYYY HH24:MI:SS''' || ') as DTH from pcnfent n, pcfornec f, pcmov m where n.numtransent=m.numtransent and n.codfornec=f.codfornec and m.dtmovlog > :dthExecucao and m.codoper in (' || '''E''' || ',' || '''EB''' || ',' || '''ET''' || ',' || '''ED''' || ') and m.codfilial = :codFilial group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, ' || '''UNICA''' || ', m.dtmovlog order by n.codfornec' ,605,'S',SYSDATE);

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, ' || '''UNICA''' || ' DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, ' || '''DD/MM/YYYY HH24:MI:SS''' || ') as DTH from pcnfent n, pcfornec f, pcmov m where n.numtransent=m.numtransent and n.codfornec=f.codfornec and m.dtmovlog > :dthExecucao and m.codoper in (' || '''E''' || ',' || '''EB''' || ',' || '''ET''' || ',' || '''ED''' || ') and m.codfilial IN (:codFilial) group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, ' || '''UNICA''' || ', m.dtmovlog order by n.codfornec' WHERE COD_ACAO_INTEGRACAO = 6 AND COD_TIPO_ACAO_INTEGRACAO = 605;

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT TO_CHAR(MAX(DTH),' || '''DD/MM/YYYY HH24:MI:SS''' || ') as DTH,
        COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA FROM (SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA,  CASE WHEN NVL(log.dataInicio,TO_DATE(' || '''01/01/1900''' || ',' || '''DD/MM/YYYY''' || ')) > NVL(p.dtultaltcom, TO_DATE(' || '''01/01/1900''' || ',' || '''DD/MM/YYYY''' || ')) THEN log.dataInicio ELSE p.dtultaltcom END as DTH FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod=e.codprod AND d.codepto=p.codepto AND sec.codsec=p.codsec AND f.codfornec=p.codfornec AND e.codfilial in (:codFilial) AND log.codprod=p.codprod AND log.codrotina in(' || '''2014''' || ',' || '''292''' || ') AND (log.datainicio > :dthExecucao OR p.dtultaltcom > :dthExecucao) UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,' || '''S''' || ',' || '''N''' || ') as EMBALAGEM_ATIVA, p.dtcadastro AS DTH from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod=e.codprod and d.codepto=p.codepto and sec.codsec=p.codsec and f.codfornec=p.codfornec and p.dtcadastro > :dthExecucao and e.codfilial in (:codFilial) )
 GROUP BY COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA' WHERE COD_ACAO_INTEGRACAO = 1;


