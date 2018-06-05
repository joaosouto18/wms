create or replace PROCEDURE EXPORTA_EXPEDICAO AS 
BEGIN

execute immediate 'DROP TABLE EXPORTACAO_EXPEDICAO';

execute immediate 'CREATE TABLE EXPORTACAO_EXPEDICAO AS
SELECT E.COD_EXPEDICAO as "COD_EXPEDICAO",
                       E.DSC_PLACA_EXPEDICAO "PLACA_EXPEDICAO",
                       TO_CHAR(E.DTH_INICIO,''DD/MM/YYYY HH24:MI:SS'') "DTH_INICIO_EXPEDICAO",
                       TO_CHAR(E.DTH_FINALIZACAO,''DD/MM/YYYY HH24:MI:SS'') "DTH_FINAL_EXPEDICAO",
                       S.DSC_SIGLA "STATUS_EXPEDICAO",
                       C.COD_CARGA_EXTERNO as "CARGA",
                       C.CENTRAL_ENTREGA as "CENTRAL_ENTREGA_CARGA",
                       C.DSC_PLACA_CARGA "PLACA_CARGA",
                       (SELECT COUNT (PP.COD_PEDIDO_PRODUTO) FROM PEDIDO PED
                           INNER JOIN ETIQUETA_SEPARACAO ETI ON PED.COD_PEDIDO = ETI.COD_PEDIDO WHERE PED.COD_CARGA = C.COD_CARGA) "QTD_ETIQUETAS_CARGA",
                       P.COD_PEDIDO "PEDIDO",
                       S2.DSC_SIGLA AS "TIPO_PEDIDO",
                       I.DSC_ITINERARIO "ITINERARIO",
                       P.DSC_LINHA_ENTREGA "LINHA_ENTREGA",
                       P.CENTRAL_ENTREGA as "CENTRAL_ENTREGA_PEDIDO",
                       P.PONTO_TRANSBORDO as "PONTO_TRANSBORDO_PEDIDO",
                       PP.COD_PRODUTO "COD_PRODUTO",
                       PP.DSC_GRADE "GRADE",
                       PROD.DSC_PRODUTO "PRODUTO",
                       F.NOM_FABRICANTE "FABRICANTE",
                       LS.DSC_LINHA_SEPARACAO "LINHA_SEPARACAO",
                       TO_CHAR(ES.DTH_CONFERENCIA,''DD/MM/YYYY HH24:MI:SS'') "DTH_CONFERENCIA_ETIQUETA",
                       ES.COD_ETIQUETA_SEPARACAO "ETIQUETA_SEPARACAO",
                       SES.DSC_SIGLA "STATUS_ETIQUETA",
                       NVL(PDL.NUM_PESO, PV.NUM_PESO) "PESO",
                       NVL(PDL.NUM_LARGURA, PV.NUM_LARGURA) "LARGURA",
                       NVL(PDL.NUM_ALTURA, PV.NUM_ALTURA) "ALTURA",
                       NVL(PDL.NUM_PROFUNDIDADE, PV.NUM_PROFUNDIDADE) "PROFUNDIDADE",
                       NVL(PDL.NUM_CUBAGEM, PV.NUM_CUBAGEM) "CUBAGEM",
                       NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) "EMBALAGEM_VOLUME",
					             NVL(DE1.DSC_DEPOSITO_ENDERECO, DE2.DSC_DEPOSITO_ENDERECO) "END_PICKING",
                       OS.COD_OS "OS",
                       CONFERENTE.NOM_PESSOA "CONFERENTE",
                       CASE WHEN OS.COD_FORMA_CONFERENCIA = ''C'' THEN ''COLETOR''
                            ELSE ''MANUAL''
                       END AS "TIPO_CONFERENCIA",
                       ES.COD_OS_TRANSBORDO "OS_TRANSBORDO",
                       CONFERENTE_TRANSBORDO.NOM_PESSOA "CONFERENTE_TRANSBORDO",
                       CLI.COD_CLIENTE_EXTERNO  "COD_CLIENTE",
                       PESSOA_CLIENTE.NOM_PESSOA "CLIENTE",
                       ENDERECO.DSC_ENDERECO "ENDERECO_CLIENTE",
                       ENDERECO.NOM_LOCALIDADE "CIDADE_CLIENTE",
                       UF.DSC_SIGLA "ESTADO_CLIENTE",
                       ENDERECO.NOM_BAIRRO "NOME_BAIRRO",
                       ENDERECO.NUM_CEP,
					   PF_CONF.NUM_CPF AS CPF_CONFERENTE,
					   PF_CONF_TRANSB.NUM_CPF AS CPF_CONFERENTE_TRANSBORDO
                 FROM EXPEDICAO E
                 LEFT JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                 LEFT JOIN SIGLA S ON E.COD_STATUS = S.COD_SIGLA
                 LEFT JOIN PEDIDO P ON C.COD_CARGA = P.COD_CARGA
                 LEFT JOIN SIGLA S2 ON S2.COD_SIGLA = P.COD_TIPO_PEDIDO
                 LEFT JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO
                 LEFT JOIN PEDIDO_PRODUTO PP ON P.COD_PEDIDO = PP.COD_PEDIDO
                 LEFT JOIN PRODUTO PROD ON PP.COD_PRODUTO = PROD.COD_PRODUTO AND PP.DSC_GRADE  = PROD.DSC_GRADE
                 LEFT JOIN FABRICANTE F ON F.COD_FABRICANTE = PROD.COD_FABRICANTE
                 LEFT JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                 LEFT JOIN ETIQUETA_SEPARACAO ES ON PP.COD_PEDIDO = ES.COD_PEDIDO AND PP.COD_PRODUTO = ES.COD_PRODUTO
                 LEFT JOIN SIGLA SES ON SES.COD_SIGLA = ES.COD_STATUS
                 LEFT JOIN PRODUTO_VOLUME PV ON ES.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                 LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
				         LEFT JOIN DEPOSITO_ENDERECO DE1 ON DE1.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
				         LEFT JOIN DEPOSITO_ENDERECO DE2 ON DE2.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                 LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                 LEFT JOIN ORDEM_SERVICO OS ON ES.COD_OS = OS.COD_OS
                 LEFT JOIN ORDEM_SERVICO OS2 ON ES.COD_OS_TRANSBORDO = OS2.COD_OS
                 LEFT JOIN PESSOA CONFERENTE ON CONFERENTE.COD_PESSOA  = OS.COD_PESSOA
				 LEFT JOIN PESSOA_FISICA PF_CONF ON PF_CONF.COD_PESSOA = CONFERENTE.COD_PESSOA
                 LEFT JOIN PESSOA CONFERENTE_TRANSBORDO ON CONFERENTE_TRANSBORDO.COD_PESSOA = OS2.COD_PESSOA
				 LEFT JOIN PESSOA_FISICA PF_CONF_TRANSB ON PF_CONF_TRANSB.COD_PESSOA = CONFERENTE_TRANSBORDO.COD_PESSOA
                 LEFT JOIN CLIENTE CLI ON CLI.COD_PESSOA = P.COD_PESSOA
                 LEFT JOIN PESSOA PESSOA_CLIENTE ON PESSOA_CLIENTE.COD_PESSOA = P.COD_PESSOA
                 LEFT JOIN PEDIDO_ENDERECO ENDERECO ON ENDERECO.COD_PEDIDO = P.COD_PEDIDO
                 LEFT JOIN SIGLA UF ON UF.COD_SIGLA = ENDERECO.COD_UF
               WHERE (E.COD_STATUS <> 466)
                 AND (E.DTH_INICIO >= TO_DATE(''01-05-2015 00:00'', ''DD-MM-YYYY HH24:MI''))
                ORDER BY E.DTH_INICIO';

END EXPORTA_EXPEDICAO;