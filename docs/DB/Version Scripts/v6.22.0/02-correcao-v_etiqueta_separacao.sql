INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.22.0','02-correcao-v_etiqueta_separacao.sql');

CREATE OR REPLACE FORCE VIEW "V_ETIQUETA_SEPARACAO" ("CODBARRAS", "STATUS", "ENTREGA", "CARGA", "LINHAENTREGA", "ITINERARIO", "CODCLIENTEEXTERNO", "CLIENTE", "CODPRODUTO", "PRODUTO", "GRADE", "FORNECEDOR", "TIPOCOMERCIALIZACAO", "ENDERECO", "LINHASEPARACAO", "ESTOQUE", "PONTOTRANSBORDO", "EXPEDICAO", "PLACAEXPEDICAO", "PLACACARGA", "CODTIPOCARGA", "TIPOCARGA", "CODCARGAEXTERNO", "CODTIPOPEDIDO", "DTHCONFERENCIA", "REIMPRESSAO", "TIPOPEDIDO", "CODBARRASPRODUTO", "CODEXTERNO")
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
         v.cod_barras                   AS codBarrasProduto,
         CASE WHEN ped.num_sequencial > 1
            THEN
              ped.cod_externo || ' - ' || NVL(num_sequencial, '')
            ELSE
              ped.cod_externo
        END as codExterno
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
     LEFT JOIN itinerario i
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
         e.cod_barras                   AS codBarrasProduto,
         CASE WHEN ped.num_sequencial > 1
            THEN
              ped.cod_externo || ' - ' || NVL(num_sequencial, '')
            ELSE
              ped.cod_externo
        END as codExterno
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
    LEFT JOIN itinerario i
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