CREATE OR REPLACE VIEW V_REL_PRODUTOS_EXPEDICAO
AS SELECT BASE.COD_EXPEDICAO,
       BASE.COD_CARGA,
       BASE.COD_CARGA_EXTERNO,
       BASE.DSC_PLACA_EXPEDICAO,
       BASE.LINHA_ENTREGA,
       BASE.COD_ITINERARIO,
       BASE.DSC_ITINERARIO,
       BASE.PRODUTO,
       BASE.DESCRICAO,
       BASE.MAPA,
       BASE.GRADE,
       BASE.QUANTIDADE - BASE.QTD_CORTE AS QUANTIDADE,
       BASE.FABRICANTE,
       BASE.NUM_PESO,
       BASE.NUM_LARGURA,
       BASE.NUM_ALTURA,
       BASE.NUM_PROFUNDIDADE,
       BASE.DSC_VOLUME,
       BASE.IND_PADRAO,
       BASE.CENTRAL_ENTREGA,
       BASE.SEQ_QUEBRA
  FROM
    (
            SELECT C.COD_EXPEDICAO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA LINHA_ENTREGA,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               PP.COD_PRODUTO PRODUTO,
               PROD.DSC_PRODUTO DESCRICAO,
               LS.DSC_LINHA_SEPARACAO MAPA,
               PP.DSC_GRADE GRADE,
               PP.QUANTIDADE QUANTIDADE,
               F.NOM_FABRICANTE FABRICANTE,
               PDL.NUM_PESO,
               PDL.NUM_LARGURA,
               PDL.NUM_ALTURA,
               PDL.NUM_PROFUNDIDADE,
               PE.DSC_EMBALAGEM DSC_VOLUME,
               PE.IND_PADRAO,
               P.CENTRAL_ENTREGA,
               0 AS QTD_CORTE,
               CASE WHEN LS.COD_LINHA_SEPARACAO = 13 THEN 0
                    WHEN LS.COD_LINHA_SEPARACAO = 15 THEN 0
                    ELSE 1
               END AS SEQ_QUEBRA
          FROM CARGA C
         INNER JOIN PEDIDO P
            ON P.COD_CARGA = C.COD_CARGA
         INNER JOIN ITINERARIO I
            ON P.COD_ITINERARIO = I.COD_ITINERARIO
         INNER JOIN PEDIDO_PRODUTO PP
            ON PP.COD_PEDIDO = P.COD_PEDIDO
         INNER JOIN PRODUTO PROD
            ON PP.COD_PRODUTO = PROD.COD_PRODUTO
           AND PP.DSC_GRADE  = PROD.DSC_GRADE
          LEFT JOIN FABRICANTE F
            ON PROD.COD_FABRICANTE = F.COD_FABRICANTE
          LEFT JOIN LINHA_SEPARACAO LS
            ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
          LEFT JOIN PRODUTO_EMBALAGEM PE
            ON PE.COD_PRODUTO = PROD.COD_PRODUTO
           AND PE.DSC_GRADE  = PROD.DSC_GRADE
          LEFT JOIN PRODUTO_DADO_LOGISTICO PDL
            ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
         WHERE NOT EXISTS (SELECT DISTINCT COD_PRODUTO,
                                  DSC_GRADE
                             FROM PRODUTO_VOLUME PV2
                            WHERE PV2.COD_PRODUTO = PP.COD_PRODUTO
                              AND PV2.DSC_GRADE   = PP.DSC_GRADE)
           AND NOT EXISTS (SELECT DISTINCT COD_PRODUTO,
                                  DSC_GRADE
                             FROM PRODUTO_EMBALAGEM PE2
                            WHERE PE2.COD_PRODUTO = PP.COD_PRODUTO
                              AND PE2.DSC_GRADE   = PP.DSC_GRADE)
         GROUP BY
               C.COD_EXPEDICAO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               PP.COD_PRODUTO,
               PP.DSC_GRADE,
               LS.DSC_LINHA_SEPARACAO,
               PROD.DSC_PRODUTO,
               PP.QUANTIDADE,
               F.NOM_FABRICANTE,
               PDL.NUM_PESO,
               PDL.NUM_LARGURA,
               PDL.NUM_ALTURA,
               PDL.NUM_PROFUNDIDADE,
               PE.DSC_EMBALAGEM,
               PE.IND_PADRAO,
               P.CENTRAL_ENTREGA,
               LS.COD_LINHA_SEPARACAO
    UNION
        SELECT C.COD_EXPEDICAO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA LINHA_ENTREGA,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               PP.COD_PRODUTO PRODUTO,
               PROD.DSC_PRODUTO DESCRICAO,
               LS.DSC_LINHA_SEPARACAO MAPA,
               PP.DSC_GRADE GRADE,
               PP.QUANTIDADE QUANTIDADE,
               F.NOM_FABRICANTE FABRICANTE,
               PV.NUM_PESO,
               PV.NUM_LARGURA,
               PV.NUM_ALTURA,
               PV.NUM_PROFUNDIDADE,
               PV.DSC_VOLUME,
               'S' AS IND_PADRAO,
               P.CENTRAL_ENTREGA,
               CASE WHEN CORTE.QTD_CORTE IS NULL THEN 0
                    ELSE CORTE.QTD_CORTE
               END AS QTD_CORTE,
               CASE WHEN LS.COD_LINHA_SEPARACAO = 13 THEN 0
                    WHEN LS.COD_LINHA_SEPARACAO = 15 THEN 0
                    ELSE 1
               END AS SEQ_QUEBRA
          FROM CARGA C
         INNER JOIN PEDIDO P
            ON P.COD_CARGA = C.COD_CARGA
         INNER JOIN ITINERARIO I
            ON P.COD_ITINERARIO = I.COD_ITINERARIO
         INNER JOIN PEDIDO_PRODUTO PP
            ON PP.COD_PEDIDO = P.COD_PEDIDO
         INNER JOIN PRODUTO PROD
            ON PP.COD_PRODUTO = PROD.COD_PRODUTO
           AND PP.DSC_GRADE  = PROD.DSC_GRADE
          LEFT JOIN FABRICANTE F
            ON PROD.COD_FABRICANTE = F.COD_FABRICANTE
          LEFT JOIN LINHA_SEPARACAO LS
            ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
         INNER JOIN PRODUTO_VOLUME PV
            ON PV.COD_PRODUTO = PROD.COD_PRODUTO
           AND PV.DSC_GRADE  = PROD.DSC_GRADE
          LEFT JOIN (SELECT COUNT(DISTINCT ES.COD_REFERENCIA) AS QTD_CORTE,
                            ES.DSC_GRADE,
                            ES.COD_PRODUTO,
                            ES.COD_PEDIDO
                       FROM ETIQUETA_SEPARACAO ES
                      WHERE ES.COD_STATUS IN (524,525)
                        AND ES.COD_PRODUTO_EMBALAGEM IS NULL
                        AND NOT ES.COD_REFERENCIA IS NULL
                      GROUP BY ES.DSC_GRADE, ES.COD_PRODUTO, ES.COD_PEDIDO) CORTE
            ON CORTE.COD_PRODUTO = PP.COD_PRODUTO
           AND CORTE.DSC_GRADE = PP.DSC_GRADE
           AND CORTE.COD_PEDIDO = PP.COD_PEDIDO
         GROUP BY
               C.COD_EXPEDICAO,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA,
               PP.COD_PRODUTO,
               PP.DSC_GRADE,
               LS.DSC_LINHA_SEPARACAO,
               PROD.DSC_PRODUTO,
               PP.QUANTIDADE,
               F.NOM_FABRICANTE,
               PV.NUM_PESO,
               PV.NUM_LARGURA,
               PV.NUM_ALTURA,
               PV.NUM_PROFUNDIDADE,
               PV.DSC_VOLUME,
               P.CENTRAL_ENTREGA,
               CORTE.QTD_CORTE,
               LS.COD_LINHA_SEPARACAO
    UNION
        SELECT C.COD_EXPEDICAO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA LINHA_ENTREGA,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               PP.COD_PRODUTO PRODUTO,
               PROD.DSC_PRODUTO DESCRICAO,
               LS.DSC_LINHA_SEPARACAO MAPA,
               PP.DSC_GRADE GRADE,
               PP.QUANTIDADE QUANTIDADE,
               F.NOM_FABRICANTE FABRICANTE,
               PDL.NUM_PESO,
               PDL.NUM_LARGURA,
               PDL.NUM_ALTURA,
               PDL.NUM_PROFUNDIDADE,
               PE.DSC_EMBALAGEM DSC_VOLUME,
               PE.IND_PADRAO,
               P.CENTRAL_ENTREGA,
               CASE WHEN CORTE.QTD_CORTE IS NULL THEN 0
                    ELSE CORTE.QTD_CORTE
               END AS QTD_CORTE,
               CASE WHEN LS.COD_LINHA_SEPARACAO = 13 THEN 0
                    WHEN LS.COD_LINHA_SEPARACAO = 15 THEN 0
                    ELSE 1
               END AS SEQ_QUEBRA
          FROM CARGA C
         INNER JOIN PEDIDO P
            ON P.COD_CARGA = C.COD_CARGA
         INNER JOIN ITINERARIO I
            ON P.COD_ITINERARIO = I.COD_ITINERARIO
         INNER JOIN PEDIDO_PRODUTO PP
            ON PP.COD_PEDIDO = P.COD_PEDIDO
         INNER JOIN PRODUTO PROD
            ON PP.COD_PRODUTO = PROD.COD_PRODUTO
           AND PP.DSC_GRADE  = PROD.DSC_GRADE
          LEFT JOIN FABRICANTE F
            ON PROD.COD_FABRICANTE = F.COD_FABRICANTE
          LEFT JOIN LINHA_SEPARACAO LS
            ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
         INNER JOIN PRODUTO_EMBALAGEM PE
            ON PE.COD_PRODUTO = PROD.COD_PRODUTO
           AND PE.DSC_GRADE  = PROD.DSC_GRADE
         INNER JOIN PRODUTO_DADO_LOGISTICO PDL
            ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
          LEFT JOIN (SELECT COUNT (DISTINCT ES.COD_PRODUTO_EMBALAGEM) AS QTD_CORTE,
                            ES.COD_PRODUTO,
                            ES.DSC_GRADE,
                            ES.COD_PEDIDO
                       FROM ETIQUETA_SEPARACAO ES
                      WHERE ES.COD_STATUS IN (524,525)
                        AND ES.COD_PRODUTO_VOLUME IS NULL
                      GROUP BY ES.COD_PRODUTO, ES.DSC_GRADE, ES.COD_PEDIDO) CORTE
            ON CORTE.COD_PRODUTO = PP.COD_PRODUTO
           AND CORTE.DSC_GRADE = PP.DSC_GRADE
           AND CORTE.COD_PEDIDO = PP.COD_PEDIDO
         GROUP BY
               C.COD_EXPEDICAO,
               C.COD_CARGA,
               C.DSC_PLACA_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               P.DSC_LINHA_ENTREGA,
               I.DSC_ITINERARIO,
               I.COD_ITINERARIO,
               PP.COD_PRODUTO,
               PP.DSC_GRADE,
               LS.DSC_LINHA_SEPARACAO,
               PROD.DSC_PRODUTO,
               PP.QUANTIDADE,
               F.NOM_FABRICANTE,
               PDL.NUM_PESO,
               PDL.NUM_LARGURA,
               PDL.NUM_ALTURA,
               PDL.NUM_PROFUNDIDADE,
               PE.DSC_EMBALAGEM,
               PE.IND_PADRAO,
               P.CENTRAL_ENTREGA,
               CORTE.QTD_CORTE,
               LS.COD_LINHA_SEPARACAO
    ) BASE
WHERE BASE.QTD_CORTE < BASE.QUANTIDADE
ORDER BY
      SEQ_QUEBRA,
      MAPA,
      PRODUTO,
      GRADE,
      IND_PADRAO DESC,
      DSC_VOLUME;
