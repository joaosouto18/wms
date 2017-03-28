/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','exemplo-novas-integracao.sql');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
 VALUES (6,1,'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, ' || '''UNICA''' || 'DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, m.dtmovlog as DTH from pcnfent n, pcfornec f, pcmov m where n.numtransent=m.numtransent and n.codfornec=f.codfornec and m.dtmov > :dthExecucao and m.codoper in (' || ''' E ''' || ',' || ''' EB ''' || ',' || ''' ET ''' || ',' || ''' ED ''' || 'and m.codfilial = :codFilial group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, ' || '''UNICA''' || ', m.dtmovlog order by n.codfornec' ,605,'S',SYSDATE);

