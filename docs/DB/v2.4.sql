-- Gerado por Oracle SQL Developer Data Modeler 3.1.0.699
--   em:        2013-03-20 15:26:42 BRT
--   site:      Oracle Database 11g
--   tipo:      Oracle Database 11g



CREATE TABLE WMS_ADM.CLIENTE 
    ( 
     COD_PESSOA NUMBER (8)  NOT NULL , 
     COD_CLIENTE_EXTERNO NUMBER (8) 
    ) LOGGING 
;


ALTER TABLE WMS_ADM.CLIENTE 
    ADD CONSTRAINT CLIENTE_PK PRIMARY KEY ( COD_PESSOA  ) ;

CREATE TABLE WMS_ADM.ETIQUETA_SEPARACAO
(
  COD_ETIQUETA_SEPARACAO NUMBER (8)  NOT NULL ,
  COD_PRODUTO_EMBALAGEM NUMBER (8) ,
  COD_PRODUTO_VOLUME NUMBER (8) ,
  DTH_CONFERENCIA DATE ,
  COD_STATUS NUMBER (8) ,
  COD_PRODUTO VARCHAR2 (20 BYTE)  NOT NULL ,
  DSC_GRADE VARCHAR2 (10 BYTE)  NOT NULL ,
  COD_PEDIDO NUMBER (8)  NOT NULL,
  DSC_REIMPRESSAO VARCHAR2 (120 BYTE) NULL,
  COD_REFERENCIA NUMBER (8) NULL
) LOGGING
;

CREATE SEQUENCE SQ_ETQ_SEPARACAO_01
	INCREMENT BY 1
	START WITH 1
	MAXVALUE 999999999999999999999999999
	MINVALUE 0
	NOCYCLE
	NOCACHE
	NOORDER

;

ALTER TABLE WMS_ADM.ETIQUETA_SEPARACAO 
    ADD CONSTRAINT ETIQUETA_PK PRIMARY KEY ( COD_ETIQUETA_SEPARACAO  ) ;


CREATE TABLE WMS_ADM.ITINERARIO 
    ( 
     COD_ITINERARIO INTEGER  NOT NULL , 
     DSC_ITINERARIO VARCHAR2 (50 BYTE) 
    ) LOGGING 
;


ALTER TABLE WMS_ADM.ITINERARIO 
    ADD CONSTRAINT ITINERARIO_PK PRIMARY KEY ( COD_ITINERARIO  ) ;


ALTER TABLE WMS_ADM.PEDIDO ADD 
    ( 
     COD_ITINERARIO INTEGER  NOT NULL 
    ) 
;

ALTER TABLE WMS_ADM.PEDIDO ADD 
    ( 
     COD_PESSOA NUMBER (8)  NOT NULL 
    ) 
;


ALTER TABLE WMS_ADM.CLIENTE 
    ADD CONSTRAINT CLIENTE_PESSOA_FK FOREIGN KEY 
    ( 
     COD_PESSOA
    ) 
    REFERENCES WMS_ADM.PESSOA 
    ( 
     COD_PESSOA
    ) 
    NOT DEFERRABLE 
;

ALTER TABLE WMS_ADM.ETIQUETA_SEPARACAO 
    ADD CONSTRAINT ETQ_SEPAR_PROD_VOL_FK FOREIGN KEY 
    ( 
     COD_PRODUTO_VOLUME
    ) 
    REFERENCES WMS_ADM.PRODUTO_VOLUME 
    ( 
     COD_PRODUTO_VOLUME
    ) 
    NOT DEFERRABLE 
;

ALTER TABLE WMS_ADM.ETIQUETA_SEPARACAO 
    ADD CONSTRAINT ETQ_SEPAR_PROD_EMB_FK FOREIGN KEY 
    ( 
     COD_PRODUTO_EMBALAGEM
    ) 
    REFERENCES WMS_ADM.PRODUTO_EMBALAGEM 
    ( 
     COD_PRODUTO_EMBALAGEM
    ) 
    NOT DEFERRABLE 
;

ALTER TABLE WMS_ADM.PEDIDO 
    ADD CONSTRAINT PEDIDO_ITINERARIO_FK FOREIGN KEY 
    ( 
     COD_ITINERARIO
    ) 
    REFERENCES WMS_ADM.ITINERARIO 
    ( 
     COD_ITINERARIO
    ) 
    NOT DEFERRABLE 
;

ALTER TABLE WMS_ADM.PEDIDO 
    ADD CONSTRAINT PEDIDO_CLIENTE_FK FOREIGN KEY 
    ( 
     COD_PESSOA
    ) 
    REFERENCES WMS_ADM.CLIENTE 
    ( 
     COD_PESSOA
    ) 
    NOT DEFERRABLE 
;


-- Relatório do Resumo do Oracle SQL Developer Data Modeler: 
-- 
-- CREATE TABLE                             3
-- CREATE INDEX                             0
-- CREATE VIEW                              0
-- ALTER TABLE                             11
-- DROP TABLE                               0
-- DROP INDEX                               0
-- CREATE TRIGGER                           0
-- ALTER TRIGGER                            0
-- CREATE SEQUENCE                          0
-- CREATE MATERIALIZED VIEW                 0
-- DROP VIEW                                0

-- 
-- ERRORS                                   0
-- WARNINGS                                 0

CREATE OR REPLACE FORCE VIEW "WMS_ADM"."V_ETIQUETA_SEPARACAO" ("CODBARRAS",
    "STATUS", "ENTREGA", "CARGA", "LINHAENTREGA", "ITINERARIO", "CODCLIENTEEXTERNO",
    "CLIENTE", "CODPRODUTO", "PRODUTO", "GRADE", "FORNECEDOR", "TIPOCOMERCIALIZACAO",
    "ENDERECO", "LINHASEPARACAO", "ESTOQUE", "EXPEDICAO", "PLACAEXPEDICAO", "CODTIPOCARGA", "TIPOCARGA",
    "CODCARGAEXTERNO", "CODTIPOPEDIDO", "DTHCONFERENCIA", "REIMPRESSAO", "TIPOPEDIDO", "CODBARRASPRODUTO")
AS
  SELECT
    es.cod_etiqueta_separacao AS codBarras,
    es.cod_status             AS status,
    ped.cod_pedido            AS Entrega,
    ped.cod_carga             AS Carga,
    ped.dsc_linha_entrega     AS LinhaEntrega,
    i.dsc_itinerario          AS Itinerario,
    cliente.cod_cliente_externo AS codClienteExterno,
    pessoa.nom_pessoa         AS Cliente,
    p.cod_produto             AS CodProduto,
    p.dsc_produto             AS Produto,
    p.dsc_grade               AS Grade,
    f.nom_fabricante          AS Fornecedor,
    v.dsc_volume              AS TipoComercializacao,
    de.dsc_deposito_endereco  AS Endereco,
    l.dsc_linha_separacao     AS linhaSeparacao,
    ped.central_entrega       AS estoque,
    c.cod_expedicao           AS Expedicao,
    c.dsc_placa_expedicao     AS placaExpedicao,
    c.cod_tipo_carga          AS codTipoCarga,
    sigla.dsc_sigla           AS tipoCarga,
    c.cod_carga_externo       AS codCargaExterno,
    ped.cod_tipo_pedido       AS codTipoPedido,
    es.dth_conferencia        AS dthConferencia,
    es.dsc_reimpressao        AS reimpressao,
    stp.dsc_sigla             AS tipoPedido,
    v.cod_barras              AS codBarrasProduto

  FROM
      etiqueta_separacao es
      INNER JOIN produto_volume v
        ON
          v.cod_produto_volume = es.cod_produto_volume
      INNER JOIN PRODUTO p
        ON
          p.cod_produto = v.cod_produto
          AND p.dsc_grade = v.dsc_grade
      LEFT JOIN deposito_endereco de
        ON
          de.cod_deposito_endereco = v.cod_deposito_endereco
      INNER JOIN pedido ped
        ON
          ped.cod_pedido = es.cod_pedido
      INNER JOIN itinerario i
        ON
          i.cod_itinerario = ped.cod_itinerario
      INNER JOIN cliente
          ON
            cliente.cod_pessoa = ped.cod_pessoa
      INNER JOIN pessoa
          ON
            cliente.cod_pessoa = pessoa.cod_pessoa
      INNER JOIN fabricante f
        ON
          f.cod_fabricante = p.cod_fabricante
      LEFT JOIN linha_separacao l
        ON
          l.cod_linha_separacao = p.cod_linha_separacao
      INNER JOIN carga c
        ON
          c.cod_carga = ped.cod_carga
      INNER JOIN sigla
          on sigla.cod_sigla = c.cod_tipo_carga
      INNER JOIN sigla stp
          on stp.cod_sigla = ped.cod_tipo_pedido
  UNION
  SELECT
    es.cod_etiqueta_separacao AS codBarras,
    es.cod_status             AS status,
    ped.cod_pedido            AS Entrega,
    ped.cod_carga             AS Carga,
    ped.dsc_linha_entrega     AS LinhaEntrega,
    i.dsc_itinerario          AS Itinerario,
    cliente.cod_cliente_externo AS codClienteExterno,
    pessoa.nom_pessoa         AS Cliente,
    p.cod_produto             AS CodProduto,
    p.dsc_produto             AS Produto,
    p.dsc_grade               AS Grade,
    f.nom_fabricante          AS Fornecedor,
    e.dsc_embalagem           AS TipoComercializacao,
    de.dsc_deposito_endereco  AS Endereco,
    l.dsc_linha_separacao     AS linhaSeparacao,
    ped.central_entrega         AS estoque,
    c.cod_expedicao           AS Expedicao,
    c.dsc_placa_expedicao     AS placaExpedicao,
    c.cod_tipo_carga          AS codTipoCarga,
    sigla.dsc_sigla           AS tipoCarga,
    c.cod_carga_externo       AS codCargaExterno,
    ped.cod_tipo_pedido       AS codTipoPedido,
    es.dth_conferencia        AS dthConferencia,
    es.dsc_reimpressao        AS reimpressao,
    stp.dsc_sigla             AS tipoPedido,
    e.cod_barras              AS codBarrasProduto

  FROM
      etiqueta_separacao es
      INNER JOIN produto_embalagem e
        ON
          e.cod_produto_embalagem = es.cod_produto_embalagem
      INNER JOIN PRODUTO p
        ON
          p.cod_produto = e.cod_produto
          AND p.dsc_grade = e.dsc_grade
      LEFT JOIN deposito_endereco de
        ON
          de.cod_deposito_endereco = e.cod_deposito_endereco
      INNER JOIN pedido ped
        ON
          ped.cod_pedido = es.cod_pedido
      INNER JOIN itinerario i
        ON
          i.cod_itinerario = ped.cod_itinerario
      INNER JOIN cliente
        ON
          cliente.cod_pessoa = ped.cod_pessoa
      INNER JOIN pessoa
        ON
          cliente.cod_pessoa = pessoa.cod_pessoa
      INNER JOIN fabricante f
        ON
          f.cod_fabricante = p.cod_fabricante
      LEFT JOIN linha_separacao l
        ON
          l.cod_linha_separacao = p.cod_linha_separacao
      INNER JOIN carga c
        ON
          c.cod_carga = ped.cod_carga
      INNER JOIN sigla
        on sigla.cod_sigla = c.cod_tipo_carga
      INNER JOIN sigla stp
        on stp.cod_sigla = ped.cod_tipo_pedido
