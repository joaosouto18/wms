/* Recursos e Actions para montar o link do relatório de produtos sem dados logisticos na expedição*/
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Relatorio de Produtos sem Dados Logisticos', 'sem-dados');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_produtos-expedicao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'sem-dados'), 'Relatorio de Produtos sem Dados Logisticos');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:etiqueta'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'sem-dados'), 'Relatorio de Produtos sem Etiquetas Impressas');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Corte', 0, 'expedicao:corte');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'index corte');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'salvar'), 'salvar corte');

INSERT INTO "MENU_ITEM" (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT MIN(COD_RECURSO_ACAO) FROM RECURSO_ACAO RA, RECURSO R WHERE RA.COD_RECURSO = R.COD_RECURSO AND NOM_RECURSO = 'expedicao:corte'), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição Mercadorias'), 'Corte expedicao', '0', '#', 'N');


INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Pendencia', 0, 'expedicao:pendencia');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:pendencia'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'index pendencia');

INSERT INTO "MENU_ITEM" (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT MIN(COD_RECURSO_ACAO) FROM RECURSO_ACAO RA, RECURSO R WHERE RA.COD_RECURSO = R.COD_RECURSO AND NOM_RECURSO = 'expedicao:pendencia'), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição Mercadorias'), 'Pendencia expedicao', '0', '#', 'N');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:etiqueta'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'index');

  CREATE OR REPLACE FORCE VIEW "WMS_ADM"."V_PRODUTO_ENDERECO" ("COD_PRODUTO", "DSC_GRADE", "NUM_PREDIO", "NUM_APARTAMENTO", "NUM_RUA", "NUM_NIVEL", "DSC_DEPOSITO_ENDERECO", "COD_DEPOSITO_ENDERECO") AS
  SELECT BASE.COD_PRODUTO,
       BASE.DSC_GRADE,
       DE.NUM_PREDIO,
       DE.NUM_APARTAMENTO,
       DE.NUM_RUA,
       DE.NUM_NIVEL,
       DE.DSC_DEPOSITO_ENDERECO,
       DE.COD_DEPOSITO_ENDERECO
  FROM (
SELECT P.COD_PRODUTO,
       P.DSC_GRADE,
       MAX(DE.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
  FROM PRODUTO P
  LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
  LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
  LEFT JOIN DEPOSITO_ENDERECO DE
    ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
    OR DE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
GROUP BY P.COD_PRODUTO, P.DSC_GRADE) BASE
LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = BASE.COD_DEPOSITO_ENDERECO;


/* A partir da linha a seguir não foi para homolog da semana do dia 22/07/13 */

INSERT INTO "ATIVIDADE" (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES ('11', 'CONF. EXPEDICAO', '1');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Relatório de Carregamento da Epedição', 0, 'expedicao:relatorio_carregamento');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_carregamento'), 31, 'imprimir');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:relatorio_carregamento') AND COD_ACAO = '31'), 116, 'Relatório de Carregamento', 0, '#');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Consultar Ordem de Serviço', 0, 'expedicao:os');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:os'), 5, 'Visualizar Os');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:os') AND COD_ACAO = '5'), 116, 'Consulta de OS', 0, '#' , 'N');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:os'), 23, 'Visualizar Conferencia');

CREATE TABLE EXPEDICAO_ANDAMENTO
(
  NUM_SEQUENCIA NUMBER (8)  NOT NULL ,
  COD_EXPEDICAO NUMBER (8)  NOT NULL ,
  COD_USUARIO NUMBER (8) ,
  DTH_ANDAMENTO DATE ,
  DSC_OBSERVACAO VARCHAR2 (512 BYTE)
) LOGGING
;

ALTER TABLE EXPEDICAO_ANDAMENTO
ADD CONSTRAINT EXPEDICAO_ANDAMENTO_PK PRIMARY KEY ( NUM_SEQUENCIA  ) ;

CREATE SEQUENCE SQ_EXP_ANDAMENTO_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

ALTER TABLE WMS_ADM.ETIQUETA_SEPARACAO ADD
(
COD_OS NUMBER (8)
)
;

ALTER TABLE WMS_ADM.ORDEM_SERVICO ADD
(
BLOQUEIO VARCHAR2 (250 BYTE),
COD_EXPEDICAO NUMBER (8)
)
;

ALTER TABLE WMS_ADM.ORDEM_SERVICO
ADD CONSTRAINT FK_ORDSE_EXPED FOREIGN KEY
  (
    COD_EXPEDICAO
  )
REFERENCES WMS_ADM.EXPEDICAO
  (
    COD_EXPEDICAO
  )
ON DELETE SET NULL
NOT DEFERRABLE
;

ALTER TABLE WMS_ADM.ETIQUETA_SEPARACAO
ADD CONSTRAINT ETQ_SEPAR_OS FOREIGN KEY
  (
    COD_OS
  )
REFERENCES WMS_ADM.ORDEM_SERVICO
  (
    COD_OS
  )
NOT DEFERRABLE
;

ALTER TABLE EXPEDICAO_ANDAMENTO
ADD CONSTRAINT EXP_AND_FK FOREIGN KEY
  (
    COD_EXPEDICAO
  )
REFERENCES WMS_ADM.EXPEDICAO
  (
    COD_EXPEDICAO
  )
NOT DEFERRABLE
;

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Finalizar pelo Coletor', 'finalizar-coletor');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:conferencia'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'finalizar-coletor'), 'finalizar expedicao pelo coletor');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:pendencia'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'view'), 'relatório etiquetas sem conferencia');

/* A partir da linha a seguir não foi para produção da semana do dia 22/07/13 */

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'endereco'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'imprimir'), 'imprimir pdf');

ALTER TABLE FILIAL
ADD ("COD_EXTERNO" VARCHAR2(20 BYTE));

UPDATE FILIAL SET COD_EXTERNO = '104';

ALTER TABLE FILIAL
ADD ("IND_RECEB_TRANSB_OBG"         VARCHAR2(20 BYTE) default 'S',
     "IND_LEIT_ETQ_PROD_TRANSB_OBG" VARCHAR2(20 BYTE) default 'S');

INSERT INTO "SIGLA" (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (530, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS DA EXPEDICAO'), 'PARCIALMENTE FINALIZADO', 'P');
INSERT INTO "SIGLA" (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (531, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS ETIQUETA SEPARACAO'), 'EXPEDIDO TRANSBORDO', 'ET');
INSERT INTO "SIGLA" (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (532, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS ETIQUETA SEPARACAO'), 'RECEBIDO TRANSBORDO', 'RT');

ALTER TABLE EXPEDICAO
ADD ("CENTRAL_PF" NUMBER(8));

CREATE OR REPLACE FORCE VIEW "WMS_ADM"."V_ETIQUETA_SEPARACAO" ("CODBARRAS", "STATUS", "ENTREGA", "CARGA", "LINHAENTREGA", "ITINERARIO", "CODCLIENTEEXTERNO", "CLIENTE", "CODPRODUTO", "PRODUTO", "GRADE", "FORNECEDOR", "TIPOCOMERCIALIZACAO", "ENDERECO", "LINHASEPARACAO", "ESTOQUE", "PONTOTRANSBORDO", "EXPEDICAO", "PLACAEXPEDICAO", "PLACACARGA", "CODTIPOCARGA", "TIPOCARGA", "CODCARGAEXTERNO", "CODTIPOPEDIDO", "DTHCONFERENCIA", "REIMPRESSAO", "TIPOPEDIDO", "CODBARRASPRODUTO")
AS
  SELECT es.cod_etiqueta_separacao AS codBarras,
         es.cod_status                  AS status,
         ped.cod_pedido                 AS Entrega,
         ped.cod_carga                  AS Carga,
         ped.dsc_linha_entrega          AS LinhaEntrega,
         i.dsc_itinerario               AS Itinerario,
         cliente.cod_cliente_externo    AS codClienteExterno,
         pessoa.nom_pessoa              AS Cliente,
         p.cod_produto                  AS CodProduto,
         p.dsc_produto                  AS Produto,
         p.dsc_grade                    AS Grade,
         f.nom_fabricante               AS Fornecedor,
         v.dsc_volume                   AS TipoComercializacao,
         de.dsc_deposito_endereco       AS Endereco,
         l.dsc_linha_separacao          AS linhaSeparacao,
         ped.central_entrega            AS estoque,
         ped.ponto_transbordo           AS pontoTransbordo,
         c.cod_expedicao                AS Expedicao,
         c.dsc_placa_expedicao          AS placaExpedicao,
         c.dsc_placa_carga              AS placaCarga,
         c.cod_tipo_carga               AS codTipoCarga,
         sigla.dsc_sigla                AS tipoCarga,
         c.cod_carga_externo            AS codCargaExterno,
         ped.cod_tipo_pedido            AS codTipoPedido,
         es.dth_conferencia             AS dthConferencia,
         es.dsc_reimpressao             AS reimpressao,
         stp.dsc_sigla                  AS tipoPedido,
         v.cod_barras                   AS codBarrasProduto
  FROM etiqueta_separacao es
    INNER JOIN produto_volume v
      ON v.cod_produto_volume = es.cod_produto_volume
    INNER JOIN PRODUTO p
      ON p.cod_produto = v.cod_produto
         AND p.dsc_grade  = v.dsc_grade
    LEFT JOIN deposito_endereco de
      ON de.cod_deposito_endereco = v.cod_deposito_endereco
    INNER JOIN pedido ped
      ON ped.cod_pedido = es.cod_pedido
    INNER JOIN itinerario i
      ON i.cod_itinerario = ped.cod_itinerario
    INNER JOIN cliente
      ON cliente.cod_pessoa = ped.cod_pessoa
    INNER JOIN pessoa
      ON cliente.cod_pessoa = pessoa.cod_pessoa
    INNER JOIN fabricante f
      ON f.cod_fabricante = p.cod_fabricante
    LEFT JOIN linha_separacao l
      ON l.cod_linha_separacao = p.cod_linha_separacao
    INNER JOIN carga c
      ON c.cod_carga = ped.cod_carga
    INNER JOIN sigla
      ON sigla.cod_sigla = c.cod_tipo_carga
    INNER JOIN sigla stp
      ON stp.cod_sigla = ped.cod_tipo_pedido
  UNION
  SELECT es.cod_etiqueta_separacao AS codBarras,
         es.cod_status                  AS status,
         ped.cod_pedido                 AS Entrega,
         ped.cod_carga                  AS Carga,
         ped.dsc_linha_entrega          AS LinhaEntrega,
         i.dsc_itinerario               AS Itinerario,
         cliente.cod_cliente_externo    AS codClienteExterno,
         pessoa.nom_pessoa              AS Cliente,
         p.cod_produto                  AS CodProduto,
         p.dsc_produto                  AS Produto,
         p.dsc_grade                    AS Grade,
         f.nom_fabricante               AS Fornecedor,
         e.dsc_embalagem                AS TipoComercializacao,
         de.dsc_deposito_endereco       AS Endereco,
         l.dsc_linha_separacao          AS linhaSeparacao,
         ped.central_entrega            AS estoque,
         ped.ponto_transbordo           AS pontoTransbordo,
         c.cod_expedicao                AS Expedicao,
         c.dsc_placa_expedicao          AS placaExpedicao,
         c.dsc_placa_carga              AS placaCarga,
         c.cod_tipo_carga               AS codTipoCarga,
         sigla.dsc_sigla                AS tipoCarga,
         c.cod_carga_externo            AS codCargaExterno,
         ped.cod_tipo_pedido            AS codTipoPedido,
         es.dth_conferencia             AS dthConferencia,
         es.dsc_reimpressao             AS reimpressao,
         stp.dsc_sigla                  AS tipoPedido,
         e.cod_barras                   AS codBarrasProduto
  FROM etiqueta_separacao es
    INNER JOIN produto_embalagem e
      ON e.cod_produto_embalagem = es.cod_produto_embalagem
    INNER JOIN PRODUTO p
      ON p.cod_produto = e.cod_produto
         AND p.dsc_grade  = e.dsc_grade
    LEFT JOIN deposito_endereco de
      ON de.cod_deposito_endereco = e.cod_deposito_endereco
    INNER JOIN pedido ped
      ON ped.cod_pedido = es.cod_pedido
    INNER JOIN itinerario i
      ON i.cod_itinerario = ped.cod_itinerario
    INNER JOIN cliente
      ON cliente.cod_pessoa = ped.cod_pessoa
    INNER JOIN pessoa
      ON cliente.cod_pessoa = pessoa.cod_pessoa
    INNER JOIN fabricante f
      ON f.cod_fabricante = p.cod_fabricante
    LEFT JOIN linha_separacao l
      ON l.cod_linha_separacao = p.cod_linha_separacao
    INNER JOIN carga c
      ON c.cod_carga = ped.cod_carga
    INNER JOIN sigla
      ON sigla.cod_sigla = c.cod_tipo_carga
    INNER JOIN sigla stp
      ON stp.cod_sigla = ped.cod_tipo_pedido;

ALTER TABLE PEDIDO
ADD ("PONTO_TRANSBORDO"         NUMBER(8),
"ENVIO_PARA_LOJA" NUMBER(8),
"CONFERIDO" NUMBER(8));