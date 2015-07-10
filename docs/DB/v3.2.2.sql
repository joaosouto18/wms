-- bloqueio inventario --

ALTER TABLE DEPOSITO_ENDERECO ADD(
   IND_INVENTARIO_BLOQUEADO CHAR DEFAULT 'N'
);

-- coluna inventario --

ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO ADD(
   CONTAGEM_INVENTARIADA NUMBER
);

-- coluna tipo fechamento --

ALTER TABLE EXPEDICAO ADD TIPO_FECHAMENTO VARCHAR2(128 BYTE);

-- implantacao linhares --

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar'), 'Consultar detalhes do Estoque');

INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Relatório Produtos Armazenados em picking incorretos', 0, 'enderecamento:relatorio_produtos-endereco-incorreto');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_produtos-endereco-incorreto'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Início');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_produtos-endereco-incorreto') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 54 AND DSC_MENU_ITEM = 'Armazenagem'), 'Produtos Com Estoque em Pickins Errados', 10, '#', 'S');

CREATE OR REPLACE VIEW V_PALETE_DISPONIVEL_PICKING
AS SELECT PK.COD_DEPOSITO_ENDERECO,
       U.COD_UNITIZADOR,
       U.NUM_LARGURA_UNITIZADOR * 100 as TAMANHO_UNITIZADOR
  FROM UNITIZADOR U, (
 SELECT COD_DEPOSITO_ENDERECO,
        CASE WHEN (TAM.TAMANHO_LONGARINA -OCUPADO) < 0 THEN 0
             ELSE (TAM.TAMANHO_LONGARINA -OCUPADO)
        END AS TAMANHO_DISPONIVEL
   FROM DEPOSITO_ENDERECO DE
   LEFT JOIN (SELECT SUM(OCUPADO) AS OCUPADO,
                     NUM_PREDIO,
                     NUM_NIVEL,
                     NUM_RUA,
                     TAMANHO_LONGARINA
                FROM (SELECT NVL(E.TAM,0) * 100 as OCUPADO,
                             DE.NUM_PREDIO,
                             DE.NUM_NIVEL,
                             DE.NUM_RUA,
                             DE.NUM_APARTAMENTO,
                             NVL(TL.TAMANHO,TP.DSC_VALOR_PARAMETRO) as TAMANHO_LONGARINA
                FROM DEPOSITO_ENDERECO DE
                LEFT JOIN (SELECT DE.COD_DEPOSITO_ENDERECO, MAX(U.NUM_LARGURA_UNITIZADOR) as TAM
                             FROM PRODUTO P
                        LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                        LEFT JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = PV.COD_NORMA_PALETIZACAO OR NP.COD_NORMA_PALETIZACAO = PDL.COD_NORMA_PALETIZACAO
                        LEFT JOIN UNITIZADOR U ON U.COD_UNITIZADOR = NP.COD_UNITIZADOR
                       INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO OR DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                       GROUP BY DE.COD_DEPOSITO_ENDERECO) E ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                LEFT JOIN TAMANHO_LONGARINA TL ON DE.NUM_PREDIO = TL.NUM_PREDIO AND DE.NUM_RUA = TL.NUM_RUA
                LEFT JOIN PARAMETRO         TP ON TP.DSC_PARAMETRO = 'TAMANHO_LONGARINA_PADRAO'
                WHERE DE.COD_CARACTERISTICA_ENDERECO = 37)
               GROUP BY NUM_PREDIO,
                        NUM_NIVEL,
                        NUM_RUA,
                        TAMANHO_LONGARINA) TAM ON TAM.NUM_PREDIO = DE.NUM_PREDIO
                                              AND TAM.NUM_NIVEL = DE.NUM_NIVEL
                                              AND TAM.NUM_RUA = DE.NUM_RUA
  WHERE DE.COD_CARACTERISTICA_ENDERECO = 37
    AND DE.COD_DEPOSITO_ENDERECO NOT IN (SELECT DISTINCT COD_DEPOSITO_ENDERECO
                                           FROM PRODUTO_EMBALAGEM
                                          WHERE COD_DEPOSITO_ENDERECO IS NOT NULL)
    AND DE.COD_DEPOSITO_ENDERECO NOT IN (SELECT DISTINCT COD_DEPOSITO_ENDERECO
                                           FROM PRODUTO_VOLUME
                                          WHERE COD_DEPOSITO_ENDERECO IS NOT NULL)
    AND DE.IND_ATIVO = 'S') PK;

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO) VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),'UTILIZA_GRADE', 'Utiliza Grade no Sistema (S/N)','N','A','S');

-- pedido endereco expedicao --

CREATE TABLE PEDIDO_ENDERECO
   ("COD_PEDIDO_ENDERECO" NUMBER(8,0),
	"COD_PEDIDO" NUMBER(8,0),
	"COD_TIPO_ENDERECO" NUMBER(8,0),
	"NUM_CEP" VARCHAR2(10 BYTE),
	"DSC_ENDERECO" VARCHAR2(100 BYTE),
	"NUM_ENDERECO" VARCHAR2(6 BYTE),
	"DSC_COMPLEMENTO" VARCHAR2(36 BYTE),
	"NOM_BAIRRO" VARCHAR2(72 BYTE),
	"NOM_LOCALIDADE" VARCHAR2(72 BYTE),
	"COD_UF" NUMBER(8,0),
	"DSC_PONTO_REFERENCIA" VARCHAR2(255 BYTE),
	"IND_ENDERECO_ECT" CHAR(1 BYTE),
	"COD_LOCALIDADE" NUMBER(8,0)
   );

CREATE SEQUENCE SQ_PEDIDO_ENDERECO_01
	INCREMENT BY 1
	START WITH 1
	MAXVALUE 999999999999999999999999999
	MINVALUE 0
	NOCYCLE
	NOCACHE
	NOORDER;

DELETE FROM PEDIDO_ENDERECO;

INSERT INTO PEDIDO_ENDERECO
SELECT SQ_PEDIDO_ENDERECO_01.NEXTVAL,
       P.COD_PEDIDO,
       E.COD_TIPO_ENDERECO,
       E.NUM_CEP,
       E.DSC_ENDERECO,
       E.NUM_ENDERECO,
       E.DSC_COMPLEMENTO,
       E.NOM_BAIRRO,
       E.NOM_LOCALIDADE,
       E.COD_UF,
       E.DSC_PONTO_REFERENCIA,
       E.IND_ENDERECO_ECT,
       E.COD_LOCALIDADE
  FROM PEDIDO P
  INNER JOIN (SELECT MAX(COD_PESSOA_ENDERECO), COD_PESSOA FROM PESSOA_ENDERECO GROUP BY COD_PESSOA) PE ON PE.COD_PESSOA = P.COD_PESSOA
  LEFT JOIN PESSOA_ENDERECO E ON E.COD_PESSOA = PE.COD_PESSOA;

-- recebimento por equipe --

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL ,'EQUIPE QUE RECEBEU OS PRODUTOS', 'equipe-recebimento');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso WHERE NOM_RECURSO LIKE 'relatorio_produto-recebido'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO LIKE 'equipe-recebimento'), 'Obter a equipe responsavel pelo recebimento dos produtos');

-- relatorio produto divergente --

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_estoque'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar-produto'), 'Relatório de Produtos Divergentes');
INSERT INTO "MENU_ITEM" (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT MIN(COD_RECURSO_ACAO) FROM RECURSO_ACAO RA, RECURSO R WHERE RA.COD_RECURSO = R.COD_RECURSO AND NOM_RECURSO = 'enderecamento:relatorio_estoque'), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Armazenagem'), 'Relatório de Produto Divergente', '0', '#', 'S');

-- ressuprimento andamento --

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:onda-ressuprimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'list'), 'Andamento Ressuprimento');

-- v rel produtos expedicao --

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

-- verifica produto volume --

 INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Consultar Produto', 'consultar-produto');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar-produto'), 'Verifica se Produto é Composto ou Uninatário');

-- volume patrimonio --

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:index'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'imprimir'), 'Relatório de Volumes Patrimônio');

--imprimir volume patrimonio --
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Impressão de Volume Patrimônio', 'imprimir-volume-patrimonio');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from RECURSO where NOM_RECURSO like 'expedicao:volume-patrimonio'), (select COD_ACAO from acao where NOM_ACAO like 'imprimir-volume-patrimonio'), 'Impressão do volume patrimonio');

--criado coluna na tabela MODELO_SEPARACAO
ALTER TABLE MODELO_SEPARACAO
ADD (IND_IMPRIME_ETQ_VOLUME VARCHAR(1));