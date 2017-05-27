INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','13 Integracao-pedidos.sql');

INSERT INTO ACAO (COD_ACAO,DSC_ACAO,NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Listar pedidos a integrar do ERP', 'listar-pedidos-erp');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO LIKE 'expedicao:pedido'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'listar-pedidos-erp'), 'Listar pedidos a integrar do ERP');

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (606, 79, 'PEDIDOS POR CARGA', 'P');
INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO, IND_UTILIZA_LOG, DTH_ULTIMA_EXECUCAO) VALUES (7,1,'select c.numcar CARGA, sum(i.qt) QTD, sum(i.qt*i.pvenda) VALOR_TOTAL from pcpedc c, pcpedi i, pccarreg car where c.numcar=car.numcar and c.numped=i.numped and c.numcar between :codCargaInicial and :codCargaFinal group by c.numcar',606,'S',null);
UPDATE ACAO_INTEGRACAO SET DSC_QUERY = 'select c.numcar CARGA, sum(i.qt) QTD, sum(i.qt*i.pvenda) VALOR_TOTAL from pcpedc c, pcpedi i, pccarreg car where c.numcar=car.numcar and c.numped=i.numped and c.numcar between :?1 and :?2 group by c.numcar' WHERE COD_ACAO_INTEGRACAO = 7;