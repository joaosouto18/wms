/*
  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 */
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA,COD_REFERENCIA_SIGLA)
  VALUES (605, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA LIKE 'INTEGRACAO'),'NOTAS FISCAIS', 'NF');

/*INTEGRAÇÃO DE PRODUTOS*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (1,1,'SELECT TO_CHAR(MAX(DTH),'||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH, COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA FROM (SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode (E.dtinativo,NULL,'||'''S'''||','||'''N'''||') as EMBALAGEM_ATIVA, log.datainicio AS DTH FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod=e.codprod AND d.codepto=p.codepto AND sec.codsec=p.codsec AND f.codfornec=p.codfornec AND e.codfilial IN ( :codFilial ) AND log.codprod=p.codprod AND log.codrotina in('||'''2014'''||','||'''292'''||') AND (log.datainicio > :dthExecucao OR p.dtultaltcom > :dthExecucao) UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,'||'''S'''||','||'''N'''||') as EMBALAGEM_ATIVA, p.dtcadastro AS DTH from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod=e.codprod and d.codepto=p.codepto and sec.codsec=p.codsec and f.codfornec=p.codfornec and p.dtcadastro > :dthExecucao and e.codfilial IN (:codFilial)) GROUP BY COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA',
  600,'S',NULL);

/*INTEGRAÇÃO DE ESTOQUE*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (2,1,'select e.codprod as COD_PRODUTO, e.qtestger as ESTOQUE_GERENCIAL, sum(e.qtestger-e.qtreserv-e.qtbloqueada) as ESTOQUE_DISPONIVEL, trunc(sum(e.qtestger*e.custoultent),2) VALOR_ESTOQUE, trunc(e.custoultent,2) CUSTO_UNITARIO, p.qtunit FATOR_UNIDADE_VENDA, p.unidade DSC_UNIDADE from pcest e, pcprodut p where e.codprod=p.codprod and e.codfilial=:codFilial AND e.qtestger > 0 group by e.codprod,e.qtestger,e.custoultent,p.qtunit,p.unidade',
  601,'S',NULL);

/*INTEGRAÇÃO DE PEDIDOS PARA IMPORTAR*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (3,1,'SELECT c.numcar CARGA, v.placa PLACA, c.numped PEDIDO, c.codpraca COD_PRACA, pr.praca DSC_PRACA, pr.rota COD_ROTA, rota.descricao DSC_ROTA, c.codcli COD_CLIENTE, cli.cliente NOME, cli.cgcent CPF_CNPJ, cli.tipofj TIPO_PESSOA, cli.enderent LOGRADOURO, cli.numeroent NUMERO, cli.bairroent BAIRRO, cli.municent CIDADE, cli.estent UF, cli.complementoent COMPLEMENTO, cli.pontorefer REFERENCIA, cli.cepent CEP, i.codprod PRODUTO, '||'''UNICA'''||' GRADE, i.qt QTD, SUM(i.qt*i.pvenda) VLR_VENDA, TO_CHAR(TO_DATE(g.datamon || '||''' '''||'||g.horamon||'||''':'''||'||g.minutomon,'||'''DD/MM/YY HH24:MI:SS'''||'),'||'''DD/MM/YYYY HH24:MI:SS'''||') AS DTH FROM pcpedc c, pcpedi i, pcpraca pr, pcrotaexp rota, pcclient cli, pccarreg g, pcveicul v WHERE c.numped=i.numped AND c.codcli=cli.codcli AND pr.codpraca=c.codpraca AND pr.rota=rota.codrota AND c.numcar=g.numcar AND g.codveiculo=v.codveiculo AND c.posicao NOT IN ('||'''C'''||') AND TO_DATE(g.datamon || '||''' '''||'||g.horamon||'||''':'''||'||g.minutomon,'||'''DD/MM/YYYY HH24:MI:SS'''||') > :dthExecucao AND c.codfilial in ( :codFilial ) AND g.datamon IS NOT NULL AND g.horamon IS NOT NULL AND g.minutomon IS NOT NULL GROUP BY c.numcar, v.placa, c.numped, c.codpraca, pr.praca, pr.rota, rota.descricao, c.codcli, cli.cliente, cli.cgcent, cli.tipofj, cli.enderent, cli.numeroent, cli.bairroent, cli.municent, cli.estent, cli.complementoent, cli.pontorefer, cli.cepent, i.codprod, i.qt, i.numseq, g.datamon, g.horamon, g.minutomon ORDER BY c.numped',
  602,'S',NULL);

/*INTEGRAÇÃO DE RESUMO DA CONFERENCIA*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (4,1,'select c.numcar CARGA, sum(i.qt) QTD, sum(i.qt*i.pvenda) valototal from pcpedc c, pcpedi i, pccarreg car where c.numcar=car.numcar and c.numped = i.numped and c.numcar= :?1 group by c.numcar',
  603,'S',NULL);

/*INTEGRAÇÃO DO DETALHAMENTO DA CONFERENCIA A NIVEL DE PEDIDO PRODUTO*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (5,1,'select i.numcar CARGA, i.numped PEDIDO, i.codprod PRODUTO, '||'''UNICA'''||' as GRADE, sum(i.qt) QTD from pcpedi i where numcar in (:?1) group by i.numcar, i.numped, i.codprod order by i.numped asc, i.codprod asc',
  604,'S',NULL);

/*INTEGRAÇÃO DE NOTAS FISCAIS DE ENTRADA - COMPLETO*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (6,1,'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, '||'''UNICA'''||' DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n inner join pcfornec f on f.codfornec = n.codfornec inner join pcmov m on m.numtransent = n.numtransent where m.dtmovlog > :dthExecucao and m.codoper in ('||'''E'''||','||'''EB'''||','||'''ET'''||') and m.codfilial IN (:codFilial) and f.revenda = '||'''S'''||' and codcont = '||'''100001'''||' group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog UNION select n.codfornec COD_FORNECEDOR, C.CLIENTE NOM_FORNECEDOR, c.cgcent CPF_CNPJ, '||'''UNICA'''||' DSC_GRADE, c.ieent INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n, pcclient c, pcmov m where n.numtransent=m.numtransent and n.codfornec=c.codcli and m.dtmovlog > :dthExecucao and m.codoper in ('||'''ED'''||') and m.codfilial IN (:codFilial) group by n.codfornec, c.cliente, c.cgcent, c.ieent, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog order by COD_FORNECEDOR',
  605,'S',NULL);

/*INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA CANCELADAS*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (7,1,'select pcnfsaid.codcli COD_FORNECEDOR, pcclient.cliente NOM_FORNECEDOR,  pcclient.cgcent  CPF_CNPJ,  ' ||'''UNICA''' || ' DSC_GRADE,  pcclient.ieent INSCRICAO_ESTADUAL, pcnfsaid.numnota NUM_NOTA_FISCAL,  pcmov.codprod COD_PRODUTO,  pcnfsaid.serie COD_SERIE_NOTA_FISCAL,  pcnfsaid.dtsaidanf DAT_EMISSAO,  pcnfsaid.placaveic DSC_PLACA_VEICULO,  pcmov.qt QTD_ITEM,  (pcmov.qt * pcmov.punit) VALOR_TOTAL, to_char(pcnfsaid.dtcancel,' || '''DD/MM/YYYY HH24:MI:SS''' || ') DTH from pcnfsaid  inner join pcmov on pcmov.numtransvenda = pcnfsaid.numtransvenda inner join pcclient on pcclient.codcli = pcnfsaid.codcli where pcnfsaid.especie = '|| '''NF''' || ' and pcmov.qt > 0 and pcmov.codoper = ' || '''S''' || ' and pcnfsaid.dtcancel > :dthExecucao and pcnfsaid.enviada = ' || '''S'''  ,
  605,'S',NULL);

/*INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA EMITIDAS NA ROTINA 1322*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (8,1, 'select pcnfsaid.numnota CARGA, ' || '''AAA0000''' || ' PLACA, pcnfsaid.numnota PEDIDO, pcclient.codpraca COD_PRACA, pcpraca.praca DSC_PRACA, pcrotaexp.codrota COD_ROTA, pcrotaexp.descricao DSC_ROTA, pcclient.codcli COD_CLIENTE, pcclient.cliente NOME, pcclient.cgcent CPF_CNPJ, pcclient.tipofj TIPO_PESSOA, pcclient.enderent LOGRADOURO, pcclient.numeroent NUMERO, pcclient.bairroent BAIRRO, pcclient.municent CIDADE, pcclient.estent UF, pcclient.complementoent COMPLEMENTO, pcclient.pontorefer REFERENCIA, pcclient.cepent CEP, pcmov.codprod PRODUTO, ' || '''UNICA''' || ' GRADE, pcmov.qt QTD, (pcmov.qt * pcmov.punit) VLR_VENDA, TO_CHAR(pcmov.dtmovlog,' || '''DD/MM/YYYY HH24:MI:SS''' || ') AS DTH from pcmov  inner join pcnfsaid on pcnfsaid.numnota = pcmov.numnota inner join pcclient on pcclient.codcli = pcnfsaid.codcli inner join pcpraca on pcpraca.codpraca = pcclient.codpraca inner join pcrotaexp on pcrotaexp.codrota = pcpraca.rota inner join pcprodut on pcprodut.codprod = pcmov.codprod where pcmov.rotinacad = ' || '''PCSIS1322.EXE''' || ' and pcprodut.revenda = ' || '''S''' || ' AND pcmov.dtmovlog > :dthExecucao ',
  602,'S',NULL);

/*INTEGRAÇÃO DE RECEBIMENTO*/
INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA,COD_REFERENCIA_SIGLA) VALUES (606,79,'RECEBIMENTO BONUS','B');
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (9,1,'select pcnfent.codfornec COD_FORNECEDOR, pcnfent.numnota NUM_NOTA, pcnfent.serie COD_SERIE_NOTA_FISCAL, pcnfent.dtent DTH_ENTRADA, pcnfent.totpeso NUM_PESO, pcnfent.numbonus COD_RECEBIMENTO_ERP, pcnfent.codfilial COD_FILIAL from pcnfent inner join pcfornec on pcfornec.codfornec = pcnfent.codfornec where pcnfent.especie = '||'''NF'''||' and pcnfent.codcont = 100001 and numbonus = (select pcnfent.numbonus from pcnfent inner join pcfornec on pcfornec.codfornec = pcnfent.codfornec where pcnfent.codfornec = :?1 and pcnfent.serie = :?2 and pcnfent.numnota = :?3 and pcnfent.especie = '||'''NF'''||' and pcnfent.codcont = 100001)',
  606,'S',NULL);

/*ATUALIZAÇÃO DE RECEBIMENTO NO ERP*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (10,1,'Update pcbonusc set datarm = SYSDATE, codfuncrm = 1 where numbonus = :?1', 606,'S',NULL);

/*ATUALIZAÇÃO DE RECEBIMENTO NO ERP*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (11,1,'Update pcbonusi set qtentrada = :?3, Qtavaria = :?4, Numlote = 01 where numbonus = :?1 and codprod = :?2', 606,'S',NULL);

/* INSERÇÃO DE RECEBIMENTO NO ERP */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (12,1,'Insert into pcbonusiconf (numbonus,codprod,dataconf,datavalidade,codfuncconf,numlote,qt, qtavaria,codauxiliar) values (:?1,:?2,:?6,:?5,1,01,:?3,:?4,:?)7', 606,'S',NULL);

/******* APENAS PARA TESTES *********/
/*INTEGRAÇÃO DE NOTAS FISCAIS DE ENTRADA*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (999,1,'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, '||'''UNICA'''|| 'DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n inner join pcfornec f on f.codfornec = n.codfornec inner join pcmov m on m.numtransent = n.numtransent where m.dtmovlog > :dthExecucao and m.codoper in ('||'''E'''||','||'''EB'''||','||'''ET'''||') and m.codfilial IN (:codFilial) and f.revenda = '||'''S'''||' and codcont = '||'''100001'''||' group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog order by n.codfornec',
  605,'S',NULL);

/******* APENAS PARA TESTES *********/
/*INTEGRAÇÃO DE NOTAS FISCAIS DE DEVOLUÇÃO*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (998,1,'select n.codfornec COD_FORNECEDOR, C.CLIENTE NOM_FORNECEDOR, c.cgcent CPF_CNPJ, '||'''UNICA'''||' DSC_GRADE, c.ieent INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, n.dtemissao DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n, pcclient c, pcmov m where n.numtransent=m.numtransent and n.codfornec=c.codcli and m.dtmovlog > :dthExecucao and m.codoper in ('||'''ED'''||') and m.codfilial IN (:codFilial) group by n.codfornec, c.cliente, c.cgcent, c.ieent, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog order by n.codfornec',
  605,'S',NULL);

/*INTEGRAÇÃO DE CORTES COM ERP*/
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (10,1,'select i.numcar CARGA, i.numped PEDIDO, i.codprod PRODUTO, '||'''UNICA'''||' as GRADE, sum(i.qt) QTD from pcpedi i where numcar in (:?1) group by i.numcar, i.numped, i.codprod order by i.numped asc, i.codprod asc',
  606,'S',NULL);
