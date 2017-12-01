/*
 *  EXEMPLO DE SCRIPT DE INTEGRAÇÃO COM A PC
 * 1  - 600 - INTEGRAÇÃO DE PRODUTOS
 * 2  - 601 - INTEGRAÇÃO DE ESTOQUE
 * 3  - 602 - INTEGRAÇÃO DE PEDIDOS PARA IMPORTAR
 * 4  - 603 - INTEGRAÇÃO DE RESUMO DA CONFERENCIA
 * 5  - 604 - INTEGRAÇÃO DO DETALHAMENTO DA CONFERENCIA A NIVEL DE PEDIDO PRODUTO
 * 6  - 605 - INTEGRAÇÃO DE NOTAS FISCAIS DE DEVOLUÇÃO DE CLIENTE
 * 7  - 605 - INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA CANCELADAS
 * 8  - 602 - INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA EMITIDAS NA ROTINA 1322
 * 9  - 606 - INTEGRAÇÃO DE RECEBIMENTO - RECEBIMENTO BONUS
 * 10 - 606 - ATUALIZAÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 * 11 - 606 - ATUALIZAÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 * 12 - 606 - INSERÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 * 13 - 605 - INTEGRAÇÃO DE NOTAS FISCAIS DE ENTRADA
 * 14 - 607 - INTEGRAÇÃO DE CORTES COM ERP
 * 15 - 608 - SETANDO A CARGA COMO IMPRESSA NO WINTHOR
 * 16 - 609 - SETANDO A CARGA COMO CONFERIDA NO WINTHOR
 * 17 - 608 - SETANDO A CARGA A SITUACAO INICIAL NO WINTHOR PARA PERMITIR CANCELAMENTO/ALTERAÇÃO DE CARGAS
 * 18 - 614 - VERIFICANDO SE A CARGA ESTA FATURADA
 * 19 - 602 - NOTAS BALCÃO
 * 20 - 615 - INTEGRAÇÂO DE NOTAS FISCAIS DE SAIDAS FATURADAS
 * 21 - 616 - LIBERAR ESTOQUE BLOQUEADO NO ERP
 * 22 - 616 - POPULAR AVARIAS/FALTAS EM ESTOQUE DO ERP
 * 23 - 616 - ALTERAR VALOR DE COLUNA PROXNUMTRANSENT TABELA PCCONSUM NO ERP
 */

DELETE FROM ACAO_INTEGRACAO_ANDAMENTO WHERE COD_ACAO_INTEGRACAO NOT IN (10,11,12);
DELETE FROM ACAO_INTEGRACAO_FILTRO WHERE COD_ACAO_INTEGRACAO NOT IN (10,11,12);
DELETE FROM ACAO_INTEGRACAO WHERE COD_ACAO_INTEGRACAO NOT IN (10,11,12);

/*
 * INTEGRAÇÃO DE PRODUTOS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (1,1,'SELECT TO_CHAR(MAX(DTH),'||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH, COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, ' || '''N''' || ' as PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA, COD_FILIAL FROM (SELECT DISTINCT p.codprod as COD_PRODUTO, p.descricao as DESCRICAO_PRODUTO, d.codepto as CODIGO_CLASSE_NIVEL_1, d.descricao as DSC_CLASSE_NIVEL_1, sec.codsec as CODIGO_CLASSE_NIVEL_2, sec.descricao as DSC_CLASSE_NIVEL_2, p.codfornec as CODIGO_FABRICANTE, f.fornecedor as DESCRICAO_FABRICANTE, e.unidade as DESCRICAO_EMBALAGEM, p.pesovariavel as PESO_VARIAVEL, e.qtunit as QTD_EMBALAGEM, e.codauxiliar as COD_BARRAS, e.pesobruto as PESO_BRUTO_EMBALAGEM, e.altura as ALTURA_EMBALAGEM, e.largura as LARGURA_EMBALAGEM, e.comprimento as PROFUNDIDADE_EMBALAGEM, e.volume as CUBAGEM_EMBALAGEM, decode(E.dtinativo,NULL,'||'''S'''||','||'''N'''||') as EMBALAGEM_ATIVA, log.datainicio AS DTH, e.codfilial AS COD_FILIAL FROM pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f, pclogrotina log WHERE p.codprod = e.codprod AND d.codepto = p.codepto AND sec.codsec = p.codsec AND f.codfornec = p.codfornec AND log.codprod = p.codprod AND log.codrotina in('||'''2014'''||','||'''292'''||') UNION SELECT DISTINCT p.codprod codigoproduto, p.descricao nomeproduto, d.codepto departamento, d.descricao nomedepto, sec.codsec secao, sec.descricao nomesecao, p.codfornec codfornecedor, f.fornecedor nomefornecedor, e.unidade, p.pesovariavel, e.qtunit, e.codauxiliar codigobarras, e.pesobruto, e.altura, e.largura, e.comprimento, e.volume, decode (E.dtinativo,NULL,'||'''S'''||','||'''N'''||') as EMBALAGEM_ATIVA, p.dtcadastro AS DTH, e.codfilial AS COD_FILIAL from pcprodut p, pcembalagem e, pcdepto d, pcsecao sec, pcfornec f where p.codprod = e.codprod and d.codepto = p.codepto and sec.codsec = p.codsec and f.codfornec = p.codfornec) WHERE 1 = 1 :where GROUP BY COD_PRODUTO, DESCRICAO_PRODUTO, CODIGO_CLASSE_NIVEL_1, DSC_CLASSE_NIVEL_1, CODIGO_CLASSE_NIVEL_2, DSC_CLASSE_NIVEL_2, CODIGO_FABRICANTE, DESCRICAO_FABRICANTE, DESCRICAO_EMBALAGEM, PESO_VARIAVEL, QTD_EMBALAGEM, COD_BARRAS, PESO_BRUTO_EMBALAGEM, ALTURA_EMBALAGEM, LARGURA_EMBALAGEM, PROFUNDIDADE_EMBALAGEM, CUBAGEM_EMBALAGEM, EMBALAGEM_ATIVA, COD_FILIAL',
  600,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 1, 610, ' AND DTH > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||') AND COD_FILIAL IN (:codFilial) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 1, 611, ' AND COD_PRODUTO = :?1 AND COD_FILIAL IN (:codFilial) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 1, 612, ' AND COD_PRODUTO IN (:?1) AND COD_FILIAL IN (:codFilial)  ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 1, 613, ' AND COD_PRODUTO BETWEEN :?1 AND :?2 AND COD_FILIAL IN (:codFilial) ');

/*
 * INTEGRAÇÃO DE ESTOQUE
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (2,1,'select e.codprod as COD_PRODUTO, e.qtestger as ESTOQUE_GERENCIAL, sum(e.qtestger-e.qtreserv-e.qtbloqueada) as ESTOQUE_DISPONIVEL, trunc(sum(e.qtestger*e.custoultent),2) VALOR_ESTOQUE, trunc(e.custoultent,2) CUSTO_UNITARIO, p.qtunit FATOR_UNIDADE_VENDA, p.unidade DSC_UNIDADE, qtindeniz as ESTOQUE_AVARIA from pcest e, pcprodut p where e.codprod=p.codprod and e.codfilial=:codFilial AND e.qtestger > 0 group by e.codprod,e.qtestger,e.custoultent,p.qtunit,p.unidade, qtindeniz ',
  601,'S',NULL);

/*
 * INTEGRAÇÃO DE PEDIDOS PARA IMPORTAR
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (3,1,
  'SELECT c.numcar CARGA, v.placa PLACA, c.numped PEDIDO, c.codpraca COD_PRACA, pr.praca DSC_PRACA, pr.rota COD_ROTA, rota.descricao DSC_ROTA, c.codcli COD_CLIENTE, cli.cliente NOME, cli.cgcent CPF_CNPJ, cli.tipofj TIPO_PESSOA, cli.enderent LOGRADOURO, cli.numeroent NUMERO, cli.bairroent BAIRRO, cli.municent CIDADE, cli.estent UF, cli.complementoent COMPLEMENTO, cli.pontorefer REFERENCIA, cli.cepent CEP, i.codprod PRODUTO, '||'''UNICA'''||' GRADE, i.qt QTD, SUM(i.qt*i.pvenda) VLR_VENDA, TO_CHAR(TO_DATE(g.datamon || '||''' '''||'||g.horamon||'||''':'''||'||g.minutomon,'||'''DD/MM/YY HH24:MI:SS'''||'),'||'''DD/MM/YYYY HH24:MI:SS'''||') AS DTH FROM pcpedc c LEFT JOIN pcpedi i ON c.numped=i.numped LEFT JOIN pcpraca pr ON pr.codpraca=c.codpraca LEFT JOIN pcrotaexp rota ON pr.rota=rota.codrota LEFT JOIN pcclient cli ON c.codcli=cli.codcli LEFT JOIN pccarreg g ON c.numcar=g.numcar LEFT JOIN pcveicul v ON g.codveiculo=v.codveiculo WHERE 1 = 1 AND c.posicao IN ('||'''M'''||') AND g.datamon IS NOT NULL AND g.horamon IS NOT NULL AND g.minutomon IS NOT NULL :where GROUP BY c.numcar, v.placa, c.numped, c.codpraca, pr.praca, pr.rota, rota.descricao, c.codcli, cli.cliente, cli.cgcent, cli.tipofj, cli.enderent, cli.numeroent, cli.bairroent, cli.municent, cli.estent, cli.complementoent, cli.pontorefer, cli.cepent, i.codprod, i.qt, i.numseq, g.datamon, g.horamon, g.minutomon ORDER BY c.numped',
  602,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 3, 610, ' AND TO_DATE(g.datamon || ' || ''' ''' || '||g.horamon||' || ''':''' || '||g.minutomon,' || '''DD/MM/YY HH24:MI:SS''' || ') > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||') AND c.codfilial in ( :codFilial )');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 3, 611, ' AND c.numcar = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 3, 612, ' AND c.numcar IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 3, 613, ' AND c.numcar BETWEEN :?1 AND :?2 ');

/*
 * INTEGRAÇÃO DE RESUMO DA CONFERENCIA
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (4,1,'select c.numcar CARGA, sum(i.qt) QTD, sum(i.qt*i.pvenda) valototal from pcpedc c, pcpedi i, pccarreg car where c.numcar=car.numcar and c.numped = i.numped :where group by c.numcar',
  603,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 4, 611, ' AND c.numcar = :?1 ');

/*
 * INTEGRAÇÃO DO DETALHAMENTO DA CONFERENCIA A NIVEL DE PEDIDO PRODUTO
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (5,1,'select i.numcar CARGA, i.numped PEDIDO, i.codprod PRODUTO, '||'''UNICA'''||' as GRADE, sum(i.qt) QTD from pcpedi i where 1 = 1 :where group by i.numcar, i.numped, i.codprod order by i.numped asc, i.codprod asc',
  604,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 5, 612, ' AND numcar in (:?1) ');

/*
 * INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA CANCELADAS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (7,1,'select pcnfsaid.codcli COD_FORNECEDOR, pcclient.cliente NOM_FORNECEDOR,  pcclient.cgcent  CPF_CNPJ,  ' ||'''UNICA''' || ' DSC_GRADE,  pcclient.ieent INSCRICAO_ESTADUAL, pcnfsaid.numnota NUM_NOTA_FISCAL,  pcmov.codprod COD_PRODUTO,  pcnfsaid.serie COD_SERIE_NOTA_FISCAL,  TO_CHAR(pcnfsaid.dtsaidanf,''' || 'DD/MM/YYYY' || ''') DAT_EMISSAO,  pcnfsaid.placaveic DSC_PLACA_VEICULO,  pcmov.qt QTD_ITEM,  (pcmov.qt * pcmov.punit) VALOR_TOTAL, to_char(pcnfsaid.dtcancel,' || '''DD/MM/YYYY HH24:MI:SS''' || ') DTH from pcnfsaid  inner join pcmov on pcmov.numtransvenda = pcnfsaid.numtransvenda inner join pcclient on pcclient.codcli = pcnfsaid.codcli where pcnfsaid.especie = '|| '''NF''' || ' and pcmov.qt > 0 and pcmov.codoper = ' || '''S''' || ' :where and pcnfsaid.enviada = ' || '''S'''  ,
  605,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 7, 610, ' AND pcnfsaid.dtcancel > TO_DATE('|| ''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||')');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 7, 611, ' AND pcnfsaid.numnota  = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 7, 612, ' AND pcnfsaid.numnota IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 7, 613, ' AND pcnfsaid.numnota BETWEEN :?1 AND :?2 ');

/*
 * INTEGRAÇÃO DE NOTAS FISCAIS DE SAIDA EMITIDAS NA ROTINA 1322
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (8,1, 'select pcnfsaid.numnota CARGA, ' || '''AAA0000''' || ' PLACA, pcnfsaid.numnota PEDIDO, pcclient.codpraca COD_PRACA, pcpraca.praca DSC_PRACA, pcrotaexp.codrota COD_ROTA, pcrotaexp.descricao DSC_ROTA, pcclient.codcli COD_CLIENTE, pcclient.cliente NOME, pcclient.cgcent CPF_CNPJ, pcclient.tipofj TIPO_PESSOA, pcclient.enderent LOGRADOURO, pcclient.numeroent NUMERO, pcclient.bairroent BAIRRO, pcclient.municent CIDADE, pcclient.estent UF, pcclient.complementoent COMPLEMENTO, pcclient.pontorefer REFERENCIA, pcclient.cepent CEP, pcmov.codprod PRODUTO, ' || '''UNICA''' || ' GRADE, decode(pcmov.qt, 0, pcmov.qtcont, pcmov.qt) QTD, (decode(pcmov.qt, 0, pcmov.qtcont, pcmov.qt) * decode(pcmov.punit, 0, pcmov.punitcont, pcmov.punit)) VLR_VENDA, TO_CHAR(pcmov.dtmovlog,' || '''DD/MM/YYYY HH24:MI:SS''' || ') AS DTH from pcmov  inner join pcnfsaid on pcnfsaid.numnota = pcmov.numnota inner join pcclient on pcclient.codcli = pcnfsaid.codcli inner join pcpraca on pcpraca.codpraca = pcclient.codpraca inner join pcrotaexp on pcrotaexp.codrota = pcpraca.rota inner join pcprodut on pcprodut.codprod = pcmov.codprod where ((pcmov.rotinacad = '||'''PCSIS1322.EXE'''||') or (pcmov.rotinacad = '||'''PCSIS1193.EXE'''||' and pcnfsaid.tipovenda = '||'''SR'''||'))  and pcprodut.revenda = ' || '''S''' || ' :where ',
  602,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 8, 610, ' AND pcmov.dtmovlog > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||') ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 8, 611, ' AND pcnfsaid.numnota = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 8, 612, ' AND pcnfsaid.numnota IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 8, 613, ' AND pcnfsaid.numnota BETWEEN :?1 AND :?2 ');

/*
 * INTEGRAÇÃO DE RECEBIMENTO - RECEBIMENTO BONUS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (9,1,'select pcnfent.codfornec COD_FORNECEDOR, pcnfent.numnota NUM_NOTA, pcnfent.serie COD_SERIE_NOTA_FISCAL, pcnfent.dtent DTH_ENTRADA, pcnfent.totpeso NUM_PESO, pcnfent.numbonus COD_RECEBIMENTO_ERP, pcnfent.codfilial COD_FILIAL from pcnfent inner join pcfornec on pcfornec.codfornec = pcnfent.codfornec where pcnfent.especie = '||'''NF'''||' and pcnfent.codcont = 100001 and numbonus = (select pcnfent.numbonus from pcnfent inner join pcfornec on pcfornec.codfornec = pcnfent.codfornec where pcnfent.especie = '||'''NF'''||' and pcnfent.codcont = 100001 :where)',
  606,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 9, 611, ' AND pcnfent.codfornec = :?1 and pcnfent.serie = :?2 and pcnfent.numnota = :?3 ');

/*
 * ATUALIZAÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (10,1,'Update pcbonusc set datarm = SYSDATE, codfuncrm = 600, dtfechamento = sysdate, codfuncfecha = 600, tipodescarga = '||'''N'''||', dtfechamentototal = sysdate where numbonus = :?1', 606,'S',NULL);

/*
 * ATUALIZAÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (11,1,'Update pcbonusi set qtentrada = :?3, Qtavaria = :?4, Numlote = 01 where numbonus = :?1 and codprod = :?2', 606,'S',NULL);

/*
 * INSERÇÃO DE RECEBIMENTO NO ERP - RECEBIMENTO BONUS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (12,1,'Insert into pcbonusiconf (numbonus,codprod,dataconf,datavalidade,codfuncconf,numlote,qt, qtavaria,codauxiliar) values (:?1,:?2,:?6,:?5,1,01,:?3,:?4,:?7)', 606,'S',NULL);

/*
 * INTEGRAÇÃO DE NOTAS FISCAIS DE DEVOLUÇÃO
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (6,1,'select n.codfornec COD_FORNECEDOR, C.CLIENTE NOM_FORNECEDOR, c.cgcent CPF_CNPJ, '||'''UNICA'''||' DSC_GRADE, c.ieent INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, TO_CHAR(n.dtemissao,' || '''DD/MM/YYYY''' || ') DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n, pcclient c, pcmov m where n.numtransent=m.numtransent and n.codfornec=c.codcli and m.codoper in ('||'''ED'''||') AND m.codfilial IN (:codFilial) :where group by n.codfornec, c.cliente, c.cgcent, c.ieent, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog order by n.codfornec',
  605,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 6, 610, ' AND m.dtmovlog > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||') ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 6, 611, ' AND n.numnota = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 6, 612, ' AND n.numnota IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 6, 613, ' AND n.numnota BETWEEN :?1 AND :?2 ');

/*
 * 13 - INTEGRAÇÃO DE NOTAS FISCAIS DE ENTRADA
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (13,1,'select n.codfornec COD_FORNECEDOR, f.fornecedor NOM_FORNECEDOR, f.cgc CPF_CNPJ, '||'''UNICA'''||' DSC_GRADE, f.ie INSCRICAO_ESTADUAL, n.numnota NUM_NOTA_FISCAL, m.codprod COD_PRODUTO, n.serie COD_SERIE_NOTA_FISCAL, to_char(n.dtemissao, '||'''DD/MM/YYYY'''||') DAT_EMISSAO, n.placaveiculo DSC_PLACA_VEICULO, sum(m.qt) QTD_ITEM, cast(sum(m.punit*m.qt) as numeric(15,2)) VALOR_TOTAL, TO_CHAR(m.dtmovlog, '||'''DD/MM/YYYY HH24:MI:SS'''||') as DTH from pcnfent n inner join pcfornec f on f.codfornec = n.codfornec inner join pcmov m on m.numtransent = n.numtransent where m.codoper in ('||'''E'''||','||'''EB'''||','||'''ET'''||','||'''ER'''||') and f.revenda = '||'''S'''||' and codcont = '||'''100001'''||' AND m.codfilial IN (:codFilial) :where group by n.codfornec, f.fornecedor, f.cgc, f.ie, n.numnota, n.serie, n.dtemissao, n.placaveiculo, m.codprod, '||'''UNICA'''||', m.dtmovlog order by n.codfornec',
  605,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 13, 610, ' AND m.dtmovlog > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||') ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 13, 611, ' AND n.numnota = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 13, 612, ' AND n.numnota IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 13, 613, ' AND n.numnota BETWEEN :?1 AND :?2 ');

/*
 * INTEGRAÇÃO DE CORTES COM ERP
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO)
  VALUES (14,1,'select i.numcar CARGA, i.numped PEDIDO, i.codprod PRODUTO, '||'''UNICA'''||' as GRADE, sum(i.qt) QTD from pcpedi i where 1 = 1 :where group by i.numcar, i.numped, i.codprod order by i.numped asc, i.codprod asc',
  607,'S',NULL);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 14, 611, ' AND numcar in (:?1) ');

UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '6,13' WHERE DSC_PARAMETRO = 'COD_INTEGRACAO_NOTAS_FISCAIS';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '3,8' WHERE DSC_PARAMETRO = 'COD_INTEGRACAO_PEDIDOS';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '2' WHERE DSC_PARAMETRO = 'COD_ACAO_INTEGRACAO_ESTOQUE';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '4' WHERE DSC_PARAMETRO = 'COD_ACAO_INTEGRACAO_RESUMO_CONFERENCIA_EXPEDICAO';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '5' WHERE DSC_PARAMETRO = 'COD_ACAO_INTEGRACAO_CONFERENCIA_EXPEDICAO';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '14' WHERE DSC_PARAMETRO = 'COD_INTEGRACAO_CORTES';


/*
 * SETANDO A CARGA COMO IMPRESSA NO WINTHOR
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (15,1,'update pccarreg set numviasmapa = 1, datamapa = sysdate where 1 = 1 :where ',
  608,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 15, 612, ' AND numcar IN (:?1) ');

/*
 * SETANDO A CARGA COMO CONFERIDA NO WINTHOR
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (16,1,'update pccarreg set dtfimcheckout = sysdate where 1 = 1 :where ',
  609,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 16, 612, ' AND numcar IN (:?1) ');

UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '15' WHERE DSC_PARAMETRO = 'ID_INTEGRACAO_INFORMA_ERP_ETQ_MAPAS_IMPRESSOS';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '16' WHERE DSC_PARAMETRO = 'ID_INTEGRACAO_FINALIZA_CONFERENCIA_ERP';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = 'S' WHERE DSC_PARAMETRO = 'IND_INFORMA_ERP_ETQ_MAPAS_IMPRESSOS_INTEGRACAO';
UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = 'S' WHERE DSC_PARAMETRO = 'IND_FINALIZA_CONFERENCIA_ERP_INTEGRACAO';


/*
 * SETANDO A CARGA A SITUACAO INICIAL NO WINTHOR PARA PERMITIR CANCELAMENTO/ALTERAÇÃO DE CARGAS
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (17,1,'update pccarreg set numviasmapa = 0, datamapa = null where 1 = 1 :where ',
  608,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 17, 612, ' AND numcar IN (:?1) ');

/*
 * VERIFICANDO SE A CARGA ESTA FATURADA
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (18,1,'SELECT DECODE(COUNT(numped),0,''' || 'N' || ''',''' || 'S' || ''') as IND_CARGA_FATURADA  FROM (SELECT 1 as numped FROM pccarreg c WHERE c.dtfat is not null AND c.numcar IN (:where) UNION SELECT nf.numnota from pcmov m inner join pcnfsaid nf on nf.numnota = m.numnota WHERE nf.numnota IN (:where) AND m.rotinacad  IN ('||'''PCSIS1322.EXE'''||','||'''PCSIS1193.EXE'''||'))',
  614,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 18, 610, ' :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 18, 611, ' :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 18, 612, ' :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 18, 613, ' :?1 ');

UPDATE PARAMETRO SET DSC_VALOR_PARAMETRO = '18' WHERE DSC_PARAMETRO = 'COD_INTEGRACAO_VERIFICA_CARGA_FINALIZADA';


/*
 * NOTAS BALCÃO
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (19,1,
  'SELECT c.numcar CARGA, v.placa PLACA, c.numped PEDIDO, c.codpraca COD_PRACA, pr.praca DSC_PRACA, pr.rota COD_ROTA, rota.descricao DSC_ROTA, c.codcli COD_CLIENTE, cli.cliente NOME, cli.cgcent CPF_CNPJ, cli.tipofj TIPO_PESSOA, cli.enderent LOGRADOURO, cli.numeroent NUMERO, cli.bairroent BAIRRO, cli.municent CIDADE, cli.estent UF, cli.complementoent COMPLEMENTO, cli.pontorefer REFERENCIA, cli.cepent CEP, i.codprod PRODUTO, '||'''UNICA'''||' GRADE, i.qt QTD, SUM(i.qt*i.pvenda) VLR_VENDA, TO_CHAR(TO_DATE(g.datamon || '||''' '''||'||g.horamon||'||''':'''||'||g.minutomon,'||'''DD/MM/YY HH24:MI:SS'''||'),'||'''DD/MM/YYYY HH24:MI:SS'''||') AS DTH FROM pcpedc c LEFT JOIN pcpedi i ON c.numped=i.numped LEFT JOIN pcpraca pr ON pr.codpraca=c.codpraca LEFT JOIN pcrotaexp rota ON pr.rota=rota.codrota LEFT JOIN pcclient cli ON c.codcli=cli.codcli LEFT JOIN pccarreg g ON c.numcar=g.numcar LEFT JOIN pcveicul v ON g.codveiculo=v.codveiculo WHERE 1 = 1 AND g.horamon > 0 AND g.minutomon > 0 AND c.posicao NOT IN ('''||'C' ||''')  AND c.origemped = ' || '''B''' || ' :where GROUP BY c.numcar, v.placa, c.numped, c.codpraca, pr.praca, pr.rota, rota.descricao, c.codcli, cli.cliente, cli.cgcent, cli.tipofj, cli.enderent, cli.numeroent, cli.bairroent, cli.municent, cli.estent, cli.complementoent, cli.pontorefer, cli.cepent, i.codprod, i.qt, i.numseq, g.datamon, g.horamon, g.minutomon ORDER BY c.numped',
  602,'S',SYSDATE);

/*
 Filtro por data desativado temporariamente
INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 19, 610, 'AND (TO_DATE(g.datamon ||'||''' '''||'||g.horamon||'||''':'''||'||g.minutomon,'||'''DD/MM/YY HH24:MI:SS'''||') > TO_DATE('||''':?1'''||','||'''DD/MM/YYYY HH24:MI:SS'''||')) AND C.CODFILIAL IN (:codFilial)');

 */
INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 19, 610, ' AND 1 = 2');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 19, 611, ' AND c.numcar = :?1 AND C.CODFILIAL IN (:codFilial)');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 19, 612, ' AND c.numcar IN (:?1) AND C.CODFILIAL IN (:codFilial)');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 19, 613, ' AND (c.numcar BETWEEN :?1 AND :?2) AND C.CODFILIAL IN (:codFilial)');

/*
 * NOTAS FISCAIS DE SAIDA
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO,COD_CONEXAO_INTEGRACAO,DSC_QUERY,COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO)
  VALUES (20,1,
  'SELECT nf.numnota as NUMERO_NF, nf.serie as SERIE_NF, nf.cgcfilial as CNPJ_EMITENTE, c.numped as PEDIDO, i.codprod COD_PRODUTO, ''' || 'UNICA' || ''' DSC_GRADE,  i.qt QTD_ITEM, TO_CHAR(nf.dthoraautorizacaosefaz,''' || 'DD/MM/YYYY hh24:MI:SS' || ''') as DTH, SUM(i.qt*i.pvenda) VLR_VENDA FROM pcpedc c INNER JOIN pcpedi i ON c.numped=i.numped INNER JOIN pcnfsaid nf ON nf.numnota = c.numnota WHERE 1 = 1  AND c.posicao IN (''' || 'F' || ''') :where GROUP BY nf.numnota, nf.serie, nf.cgcfilial, c.numped, i.codprod, i.qt, nf.dthoraautorizacaosefaz ',
  615,'S',SYSDATE);

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 20, 610, ' AND nf.dthoraautorizacaosefaz > TO_DATE(''' || ':?1' || ''',''' || 'DD/MM/YYYY HH24:MI:SS' || ''')');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 20, 611, ' AND nf.numnota = :?1 ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 20, 612, ' AND nf.numnota IN (:?1) ');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO)
  VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 20, 613, ' AND nf.numnota BETWEEN :?1 AND :?2 ');

/*
 * DESBLOQUEAR ESTOQUE DO ERP
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (21,1,
  'UPDATE PCEST SET QTINDENIZ = QTINDENIZ + :?2, CODDEVOL = :?3, MOTIVOBLOQESTOQUE = '||''':?4'''||', QTBLOQUEADA = QTBLOQUEADA - :?5 WHERE CODPROD = :?1',
  606,'S',SYSDATE,'N');

/*
 * POPULAR AVARIAS/FALTAS NO ERP
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (23,1,'UPDATE PCCONSUM SET PROXNUMTRANSENT = PROXNUMTRANSENT + 1',606,'S',SYSDATE,'N');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (22,1,'INSERT INTO PCMOVAVARIA (DATA, CODPROD, CODCLI, QT, CODOPER, CODFUNCLANC, NUMNOTA, NUMTRANSVENDA, NUMTRANSENT, OBS, CODFILIAL, CODDEVOL) VALUES (SYSDATE, :?1, 0, :?2, '||'''E'''||', :?3, :?4, 0, (SELECT MAX(PROXNUMTRANSEN) FROM PCCONSUM), null, :?5, null)',606,'S',SYSDATE,'N');

/*
 * CANCELA CARGAS PROVENIENTES DO ERP
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (24, 2, 'SELECT COD_CARGA_EXTERNO, TO_CHAR(DTH_CANCELAMENTO, '||'''DD/MM/YYYY HH24:MI:SS'''||') DTH_CANCELAMENTO FROM TR_CANCELAMENTO_CARGA', 617, 'S', SYSDATE, 'N');

/**
  * KIT
 */
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (25, 2, 'SELECT CARGA, PLACA, PEDIDO, TIPO_PEDIDO, COD_PRACA, DSC_PRACA, COD_ROTA, DSC_ROTA, COD_CLIENTE, NOME, CPF_CNPJ, TIPO_PESSOA, LOGRADOURO, NUMERO, BAIRRO, CIDADE, UF, COMPLEMENTO, REFERENCIA, CEP, PRODUTO, GRADE, QTD, VLR_VENDA FROM TR_PEDIDO',602, 'S', SYSDATE, 'N');

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO) VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 25, 611, 'AND IND_PROCESSADO = '||'''N'''||' OR IND_PROCESSADO IS NULL');

UPDATE ACAO_INTEGRACAO SET IND_TIPO_CONTROLE = 'F', TABELA_REFERENCIA = 'TR_PEDIDO' WHERE COD_ACAO_INTEGRACAO = 25;

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT CARGA, PLACA, PEDIDO, TIPO_PEDIDO, COD_PRACA, DSC_PRACA, COD_ROTA, DSC_ROTA, COD_CLIENTE, NOME, CPF_CNPJ, TIPO_PESSOA, LOGRADOURO, NUMERO, BAIRRO, CIDADE, UF, COMPLEMENTO, REFERENCIA, CEP, PRODUTO, GRADE, QTD, VLR_VENDA, IND_PROCESSADO FROM TR_PEDIDO WHERE 1 = 1 :where ORDER BY PEDIDO, PRODUTO' WHERE COD_ACAO_INTEGRACAO = 25;


INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
  VALUES (26, 2, 'SELECT COD_FORNECEDOR, COD_PRODUTO, NOM_FORNECEDOR, CPF_CNPJ, DSC_GRADE, INSCRICAO_ESTADUAL, NUM_NOTA_FISCAL, COD_SERIE_NOTA_FISCAL, TO_CHAR(DAT_EMISSAO,'||'''DD/MM/YYYY HH24:MI:SS'''||') DAT_EMISSAO, TIPO_NOTA, DSC_PLACA_VEICULO, QTD_ITEM, VALOR_TOTAL, IND_PROCESSADO FROM TR_NOTA_FISCAL_ENTRADA WHERE 1 = 1 :where',
  605, 'S', SYSDATE, 'N');

UPDATE ACAO_INTEGRACAO SET IND_TIPO_CONTROLE = 'F', TABELA_REFERENCIA = 'TR_NOTA_FISCAL_ENTRADA' WHERE COD_ACAO_INTEGRACAO = 26;

INSERT INTO ACAO_INTEGRACAO_FILTRO (COD_ACAO_INTEGRACAO_FILTRO, COD_ACAO_INTEGRACAO, COD_TIPO_REGISTRO, DSC_FILTRO) VALUES (SQ_ACAO_INTEGRACAO_FILTRO_01.NEXTVAL, 26, 611, 'AND IND_PROCESSADO = '||'''N'''||' OR IND_PROCESSADO IS NULL');

UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'SELECT COD_FORNECEDOR, COD_PRODUTO, NOM_FORNECEDOR, CPF_CNPJ, DSC_GRADE, INSCRICAO_ESTADUAL, NUM_NOTA_FISCAL, COD_SERIE_NOTA_FISCAL, TO_CHAR(DAT_EMISSAO,''DD/MM/YYYY'') DAT_EMISSAO, TIPO_NOTA, DSC_PLACA_VEICULO, QTD_ITEM, VALOR_TOTAL, IND_PROCESSADO FROM TR_NOTA_FISCAL_ENTRADA WHERE 1 = 1 :where ORDER BY NUM_NOTA_FISCAL, COD_SERIE_NOTA_FISCAL' WHERE COD_ACAO_INTEGRACAO = 26;


/**
  * TRIGGERS
 */

--CANCELAMENTO DE CARGAS
CREATE OR REPLACE TRIGGER WMSIMPERIUM.IMPERIUM_TR_CANCELA_CARGA
 AFTER
  UPDATE
 ON marcosart.pccarreg
REFERENCING NEW AS NEW OLD AS OLD
 FOR EACH ROW
declare

begin

  if :new.dt_cancel is not null then

      INSERT INTO wms_adm.tr_cancelamento_carga@dbIMPERIUM(cod_carga_externo, dth_cancelamento) values (:old.numcar, sysdate);

  end if;


  end;

--VENDA BALCAO
CREATE OR REPLACE TRIGGER WMSIMPERIUM.TRG_IMPERIUM_VENDA_BALCAO
 BEFORE
 INSERT
 ON marcosart.pccarreg
 REFERENCING OLD AS OLD NEW AS NEW
 FOR EACH ROW
declare

vcarga          number;
vpedido         number;
vcodpraca       number;
vpraca          varchar(100);
vcodrota        number;
vrota           varchar(100);
vcodcli         number;
vrazao          varchar(200);
vcnpj           varchar(20);
vtipofj         varchar(1);
vendereco       varchar(200);
vnumero         varchar(10);
vbairro         varchar(100);
vcidade         varchar(100);
vuf             varchar(2);
vcomplemento    varchar(200);
vreferencia     varchar(100);
vcep            varchar(10);
vcodprod        number;
vgrade          varchar(10);
vqtd            number(15,4);
vvalor          number(15,2);
vveiculo        varchar(10);

begin

    if (:new.numcar = 0) and (:new.destino = 'VENDA BALCAO') then

select pcveicul.placa, pcpedc.numped, pcpedc.codpraca, pcpraca.praca, pcpraca.rota, pcrotaexp.descricao, pcpedc.codcli,
pcclient.cliente, pcclient.cgcent, pcclient.tipofj,
pcclient.enderent, pcclient.numeroent, pcclient.bairroent,
pcclient.municent, pcclient.estent, pcclient.complementoent,
pcclient.pontorefer, pcclient.cepent, pcpedi.codprod,
pcpedi.qt, (pcpedi.qt * pcpedi.pvenda) VLR_VENDA

into vveiculo, vpedido, vcodpraca, vpraca, vcodrota, vrota,
     vcodcli, vrazao, vcnpj, vtipofj, vendereco, vnumero,
     vbairro, vcidade, vuf, vcomplemento, vreferencia, vcep,
     vcodprod, vqtd, vvalor
from marcosart.pcpedc
inner join marcosart.pcpedi on
pcpedi.numped = pcpedc.numped
inner join (select codcli, cliente, cepent,
codpraca, pontorefer, complementoent,
estent, municent, bairroent,
enderent, numeroent,
cgcent, tipofj
from marcosart.pcclient) pcclient on pcclient.codcli = pcpedc.codcli
inner join (select pcveicul.codveiculo, pcveicul.placa from marcosart.pcveicul) pcveicul on pcveicul.codveiculo = :new.codveiculo
inner join (select pcpraca.praca, pcpraca.codpraca, pcpraca.rota from marcosart.pcpraca) pcpraca on pcpraca.codpraca = pcclient.codpraca
inner join (select pcrotaexp.codrota, pcrotaexp.descricao from marcosart.pcrotaexp) pcrotaexp on pcrotaexp.codrota = pcpraca.rota
where pcpedc.numcar = :new.numcar and PCPEDC.ORIGEMPED IN ('B');

    insert into TR_PEDIDO@DBIMPERIUM
    (CARGA,
 PLACA,
     PEDIDO,
     TIPO_PEDIDO,
     COD_PRACA,
     DSC_PRACA,
     COD_ROTA,
     DSC_ROTA,
     COD_CLIENTE,
     NOME,
     CPF_CNPJ,
     TIPO_PESSOA,
     LOGRADOURO,
     NUMERO,
     BAIRRO,
     CIDADE,
     UF,
     COMPLEMENTO,
     REFERENCIA,
     CEP,
     PRODUTO,
     GRADE,
     QTD,
     VLR_VENDA)

    values

    (:new.numcar,
     vveiculo,
     vpedido,
     '521',
     vcodpraca,
     vpraca,
     vcodrota,
     vrota,
     vcodcli,
     vrazao,
     vcnpj,
     vtipofj,
     vendereco,
     vnumero,
     vbairro,
     vcidade,
     vuf,
     vcomplemento,
     vreferencia,
     vcep,
     vcodprod,
     'UNICA',
     vqtd,
     vvalor);

     end if;
end;

--SAIDA PRODUTOS KIT
CREATE OR REPLACE TRIGGER WMSIMPERIUM.TRG_IMPERIUM_SAIDA_KIT
 BEFORE
 INSERT
 ON marcosart.PCMOV
 REFERENCING OLD AS OLD NEW AS NEW
 FOR EACH ROW
declare

vcodigo Varchar(2);
vrazao Varchar(40);
vcodcli number;
vcnpj Varchar(14);
vie Varchar(20);
vendereco  Varchar(200);
vbairro  Varchar(100);
vcidade  Varchar(100);
vuf Varchar(2);
vcodpraca number;
vpraca  Varchar(100);
vcodrota number;
vrota Varchar(100);
vtipofj Varchar(1);
vnumero Varchar(10);
vcep varchar(9);

begin

    select
    pcfilial.codigo,
    pcfilial.razaosocial,
    pcfilial.codcli,
    pcfilial.cgc,
    pcfilial.ie,
    pcfilial.endereco,
    pcfilial.bairro,
    pcfilial.cidade,
    pcfilial.UF,
    pcpraca.codpraca,
    pcpraca.praca,
    pcrotaexp.codrota,
    pcrotaexp.descricao,
    pcclient.tipofj,
    pcfilial.numero,
    pcfilial.cep
    into
    vcodigo,
    vrazao,
    vcodcli,
    vcnpj,
    vie,
    vendereco,
    vbairro,
    vcidade,
    vuf,
    vcodpraca,
    vpraca,
    vcodrota,
    vrota,
    vtipofj,
    vnumero,
    vcep
    from marcosart.pcfilial
    inner join marcosart.pcclient on
    pcclient.codcli = pcfilial.codcli
    inner join marcosart.pcpraca on
    pcpraca.codpraca = pcclient.codpraca
    inner join marcosart.pcrotaexp on
    pcrotaexp.codrota = pcpraca.rota
    where codigo = :new.codfilial;


    if (:new.codoper = 'SP') then

     insert into TR_PEDIDO@DBIMPERIUM
    (CARGA,
     PLACA,
     PEDIDO,
     TIPO_PEDIDO,
     COD_PRACA,
     DSC_PRACA,
     COD_ROTA,
     DSC_ROTA,
     COD_CLIENTE,
     NOME,
     CPF_CNPJ,
     TIPO_PESSOA,
     LOGRADOURO,
     NUMERO,
     BAIRRO,
     CIDADE,
     UF,
     COMPLEMENTO,
     REFERENCIA,
     CEP,
     PRODUTO,
     GRADE,
     QTD,
     VLR_VENDA)

    VALUES

    (:new.numtransvenda,
     'AAA-0000',
     :new.numtransvenda,
     '1',
     vcodpraca,
     vpraca,
     vcodrota,
     vrota,
     vcodcli,
     vrazao,
     vcnpj,
     vtipofj,
     vendereco,
     vnumero,
     vbairro,
     vcidade,
     vuf,
     null,
     null,
     vcep,
     :new.codprod,
     'UNICA',
     :new.qt,
     :new.qt * :new.custofin);



    end if;
end;

--


--ROTINA 1322
CREATE OR REPLACE TRIGGER WMSIMPERIUM.trg_imperium_simple_remessa
 BEFORE
  INSERT
 ON marcosart.pcmov
REFERENCING NEW AS NEW OLD AS OLD
 FOR EACH ROW
declare



vcarga          number;
vpedido         number;
vcodpraca       number;
vpraca          varchar(100);
vcodrota        number;
vrota           varchar(100);
vcodcli         number;
vrazao          varchar(200);
vcnpj           varchar(20);
vtipofj         varchar(1);
vendereco       varchar(200);
vnumero         varchar(10);
vbairro         varchar(100);
vcidade         varchar(100);
vuf             varchar(2);
vcomplemento    varchar(200);
vreferencia     varchar(100);
vcep            varchar(10);
vcodprod        number;
vgrade          varchar(10);
vqtd            number(15,4);
vvalor          number(15,2);


begin

    if (:new.rotinacad = 'PCSIS1322.EXE') then


    select pccli.codcli, pccli.cliente, pccli.cgcent, pccli.tipofj,
           pccli.enderent, pccli.numeroent, pccli.bairroent, pccli.municent,
           pccli.estent, pccli.complementoent, pccli.pontorefer, pccli.cepent,
           pccli.codpraca

           into vcodcli, vrazao, vcnpj, vtipofj, vendereco, vnumero,
                vbairro, vcidade, vuf, vcomplemento, vreferencia, vcep, vcodpraca
           from marcosart.pcclient pccli where codcli = :new.codcli;


    select pcpraca.codpraca, pcpraca.praca, pcrotaexp.codrota, pcrotaexp.descricao
           into vcodpraca, vpraca, vcodrota, vrota
           from marcosart.pcpraca
           inner join marcosart.pcrotaexp on
           pcrotaexp.codrota = pcpraca.rota
           where pcpraca.codpraca = vcodpraca;


    insert into TR_PEDIDO@DBIMPERIUM
    (CARGA,
     PLACA,
     PEDIDO,
     TIPO_PEDIDO,
     COD_PRACA,
     DSC_PRACA,
     COD_ROTA,
     DSC_ROTA,
     COD_CLIENTE,
     NOME,
     CPF_CNPJ,
     TIPO_PESSOA,
     LOGRADOURO,
     NUMERO,
     BAIRRO,
     CIDADE,
     UF,
     COMPLEMENTO,
     REFERENCIA,
     CEP,
     PRODUTO,
     GRADE,
     QTD,
     VLR_VENDA)

    values

    (:new.numcar,
     'AAA0000',
     :new.numped,
     '521',
     vcodpraca,
     vpraca,
     vcodrota,
     vrota,
     vcodcli,
     vrazao,
     vcnpj,
     vtipofj,
     vendereco,
     vnumero,
     vbairro,
     vcidade,
     vuf,
     vcomplemento,
     vreferencia,
     vcep,
     :new.codprod,
     'UNICA',
     :new.qt,
     :new.punit * :new.qt);

    end if;
end;
