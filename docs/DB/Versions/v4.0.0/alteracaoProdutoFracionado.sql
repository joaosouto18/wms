INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '4.0.0','alteracaoProdutoFracionado.sql');

-- TABELA ESTOQUE
ALTER TABLE ESTOQUE ADD QTD_TEMP NUMBER(13,3);
UPDATE ESTOQUE SET QTD_TEMP = QTD;
UPDATE ESTOQUE SET QTD = 0;
ALTER TABLE ESTOQUE MODIFY QTD NUMBER(13,3);
UPDATE ESTOQUE SET QTD = QTD_TEMP;
ALTER TABLE ESTOQUE DROP COLUMN QTD_TEMP;



-- TABELA CONF_RECEB_REENTREGA
ALTER TABLE CONF_RECEB_REENTREGA ADD QTD_CONFERIDA_TEMP NUMBER(13,3);
UPDATE CONF_RECEB_REENTREGA SET QTD_CONFERIDA_TEMP = QTD_CONFERIDA;
UPDATE CONF_RECEB_REENTREGA SET QTD_CONFERIDA = 0;
ALTER TABLE CONF_RECEB_REENTREGA MODIFY QTD_CONFERIDA NUMBER(13,3);
UPDATE CONF_RECEB_REENTREGA SET QTD_CONFERIDA = QTD_CONFERIDA_TEMP;
ALTER TABLE CONF_RECEB_REENTREGA DROP COLUMN QTD_CONFERIDA_TEMP;

ALTER TABLE CONF_RECEB_REENTREGA ADD QTD_EMBALAGEM_CONFERIDA_TEMP NUMBER(13,3);
UPDATE CONF_RECEB_REENTREGA SET QTD_EMBALAGEM_CONFERIDA_TEMP = QTD_EMBALAGEM_CONFERIDA;
UPDATE CONF_RECEB_REENTREGA SET QTD_EMBALAGEM_CONFERIDA = 0;
ALTER TABLE CONF_RECEB_REENTREGA MODIFY QTD_EMBALAGEM_CONFERIDA NUMBER(13,3);
UPDATE CONF_RECEB_REENTREGA SET QTD_EMBALAGEM_CONFERIDA = QTD_EMBALAGEM_CONFERIDA_TEMP;
ALTER TABLE CONF_RECEB_REENTREGA DROP COLUMN QTD_EMBALAGEM_CONFERIDA_TEMP;



-- TABELA ETIQUETA_SEPARACAO
ALTER TABLE ETIQUETA_SEPARACAO ADD QTD_PRODUTO_TEMP NUMBER(13,3);
UPDATE ETIQUETA_SEPARACAO SET QTD_PRODUTO_TEMP = QTD_PRODUTO;
UPDATE ETIQUETA_SEPARACAO SET QTD_PRODUTO = 0;
ALTER TABLE ETIQUETA_SEPARACAO MODIFY QTD_PRODUTO NUMBER(13,3);
UPDATE ETIQUETA_SEPARACAO SET QTD_PRODUTO = QTD_PRODUTO_TEMP;
ALTER TABLE ETIQUETA_SEPARACAO DROP COLUMN QTD_PRODUTO_TEMP;



-- TABELA HISTORICO_ESTOQUE
ALTER TABLE HISTORICO_ESTOQUE ADD QTD_TEMP NUMBER(13,3);
UPDATE HISTORICO_ESTOQUE SET QTD_TEMP = QTD;
UPDATE HISTORICO_ESTOQUE SET QTD = 0;
ALTER TABLE HISTORICO_ESTOQUE MODIFY QTD NUMBER(13,3);
UPDATE HISTORICO_ESTOQUE SET QTD = QTD_TEMP;
ALTER TABLE HISTORICO_ESTOQUE DROP COLUMN QTD_TEMP;



-- TABELA INVENTARIO_CONTAGEM_ENDERECO
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO ADD QTD_AVARIA_TEMP NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_AVARIA_TEMP = QTD_AVARIA;
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_AVARIA = 0;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO MODIFY QTD_AVARIA NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_AVARIA = QTD_AVARIA_TEMP;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO DROP COLUMN QTD_AVARIA_TEMP;

ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO ADD QTD_CONTADA_TEMP NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_CONTADA_TEMP = QTD_CONTADA;
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_CONTADA = 0;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO MODIFY QTD_CONTADA NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_CONTADA = QTD_CONTADA_TEMP;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO DROP COLUMN QTD_CONTADA_TEMP;

ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO ADD QTD_DIVERGENCIA_TEMP NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_DIVERGENCIA_TEMP = QTD_DIVERGENCIA;
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_DIVERGENCIA = 0;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO MODIFY QTD_DIVERGENCIA NUMBER(13,3);
UPDATE INVENTARIO_CONTAGEM_ENDERECO SET QTD_CONTADA = QTD_DIVERGENCIA_TEMP;
ALTER TABLE INVENTARIO_CONTAGEM_ENDERECO DROP COLUMN QTD_DIVERGENCIA_TEMP;



-- TABELA MAPA_SEPARACAO_CONFERENCIA
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA ADD QTD_EMBALAGEM_TEMP NUMBER(13,3);
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_EMBALAGEM_TEMP = QTD_EMBALAGEM;
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_EMBALAGEM = 0;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA MODIFY QTD_EMBALAGEM NUMBER(13,3);
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_EMBALAGEM = QTD_EMBALAGEM_TEMP;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA DROP COLUMN QTD_EMBALAGEM_TEMP;

ALTER TABLE MAPA_SEPARACAO_CONFERENCIA ADD QTD_CONFERIDA_TEMP NUMBER(13,3);
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_CONFERIDA_TEMP = QTD_CONFERIDA;
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_CONFERIDA = 0;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA MODIFY QTD_CONFERIDA NUMBER(13,3);
UPDATE MAPA_SEPARACAO_CONFERENCIA SET QTD_CONFERIDA = QTD_CONFERIDA_TEMP;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA DROP COLUMN QTD_CONFERIDA_TEMP;



-- TABELA MAPA_SEPARACAO_PRODUTO
ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD QTD_EMBALAGEM_TEMP NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_EMBALAGEM_TEMP = QTD_EMBALAGEM;
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_EMBALAGEM = 0;
ALTER TABLE MAPA_SEPARACAO_PRODUTO MODIFY QTD_EMBALAGEM NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_EMBALAGEM = QTD_EMBALAGEM_TEMP;
ALTER TABLE MAPA_SEPARACAO_PRODUTO DROP COLUMN QTD_EMBALAGEM_TEMP;

ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD QTD_SEPARAR_TEMP NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_SEPARAR_TEMP = QTD_SEPARAR;
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_SEPARAR = 0;
ALTER TABLE MAPA_SEPARACAO_PRODUTO MODIFY QTD_SEPARAR NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_SEPARAR = QTD_SEPARAR_TEMP;
ALTER TABLE MAPA_SEPARACAO_PRODUTO DROP COLUMN QTD_SEPARAR_TEMP;

ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD QTD_CORTADO_TEMP NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_CORTADO_TEMP = QTD_CORTADO;
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_CORTADO = 0;
ALTER TABLE MAPA_SEPARACAO_PRODUTO MODIFY QTD_CORTADO NUMBER(13,3);
UPDATE MAPA_SEPARACAO_PRODUTO SET QTD_CORTADO = QTD_CORTADO_TEMP;
ALTER TABLE MAPA_SEPARACAO_PRODUTO DROP COLUMN QTD_CORTADO_TEMP;



-- TABELA NORMA_PALETIZACAO
ALTER TABLE NORMA_PALETIZACAO ADD NUM_LASTRO_TEMP NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_LASTRO_TEMP = NUM_LASTRO;
UPDATE NORMA_PALETIZACAO SET NUM_LASTRO = 0;
ALTER TABLE NORMA_PALETIZACAO MODIFY NUM_LASTRO NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_LASTRO = NUM_LASTRO_TEMP;
ALTER TABLE NORMA_PALETIZACAO DROP COLUMN NUM_LASTRO_TEMP;

ALTER TABLE NORMA_PALETIZACAO ADD NUM_CAMADAS_TEMP NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_CAMADAS_TEMP = NUM_CAMADAS;
UPDATE NORMA_PALETIZACAO SET NUM_CAMADAS = 0;
ALTER TABLE NORMA_PALETIZACAO MODIFY NUM_CAMADAS NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_CAMADAS = NUM_CAMADAS_TEMP;
ALTER TABLE NORMA_PALETIZACAO DROP COLUMN NUM_CAMADAS_TEMP;

ALTER TABLE NORMA_PALETIZACAO ADD NUM_NORMA_TEMP NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_NORMA_TEMP = NUM_NORMA;
UPDATE NORMA_PALETIZACAO SET NUM_NORMA = 0;
ALTER TABLE NORMA_PALETIZACAO MODIFY NUM_NORMA NUMBER(13,3);
UPDATE NORMA_PALETIZACAO SET NUM_NORMA = NUM_NORMA_TEMP;
ALTER TABLE NORMA_PALETIZACAO DROP COLUMN NUM_NORMA_TEMP;



-- TABELA NOTA_FISCAL_ITEM
ALTER TABLE NOTA_FISCAL_ITEM ADD QTD_ITEM_TEMP NUMBER(13,3);
UPDATE NOTA_FISCAL_ITEM SET QTD_ITEM_TEMP = QTD_ITEM;
UPDATE NOTA_FISCAL_ITEM SET QTD_ITEM = 0;
ALTER TABLE NOTA_FISCAL_ITEM MODIFY QTD_ITEM NUMBER(13,3);
UPDATE NOTA_FISCAL_ITEM SET QTD_ITEM = QTD_ITEM_TEMP;
ALTER TABLE NOTA_FISCAL_ITEM DROP COLUMN QTD_ITEM_TEMP;



-- TABELA NOTA_FISCAL_SAIDA_PRODUTO
ALTER TABLE NOTA_FISCAL_SAIDA_PRODUTO ADD QUANTIDADE_TEMP NUMBER(13,3);
UPDATE NOTA_FISCAL_SAIDA_PRODUTO SET QUANTIDADE_TEMP = QUANTIDADE;
UPDATE NOTA_FISCAL_SAIDA_PRODUTO SET QUANTIDADE = 0;
ALTER TABLE NOTA_FISCAL_SAIDA_PRODUTO MODIFY QUANTIDADE NUMBER(13,3);
UPDATE NOTA_FISCAL_SAIDA_PRODUTO SET QUANTIDADE = QUANTIDADE_TEMP;
ALTER TABLE NOTA_FISCAL_SAIDA_PRODUTO DROP COLUMN QUANTIDADE_TEMP;



-- TABELA ONDA_RESSUPRIMENTO_OS_PRODUTO
ALTER TABLE ONDA_RESSUPRIMENTO_OS_PRODUTO ADD QTD_TEMP NUMBER(13,3);
UPDATE ONDA_RESSUPRIMENTO_OS_PRODUTO SET QTD_TEMP = QTD;
UPDATE ONDA_RESSUPRIMENTO_OS_PRODUTO SET QTD = 0;
ALTER TABLE ONDA_RESSUPRIMENTO_OS_PRODUTO MODIFY QTD NUMBER(13,3);
UPDATE ONDA_RESSUPRIMENTO_OS_PRODUTO SET QTD = QTD_TEMP;
ALTER TABLE ONDA_RESSUPRIMENTO_OS_PRODUTO DROP COLUMN QTD_TEMP;



-- TABELA ONDA_RESSUPRIMENTO_PEDIDO
ALTER TABLE ONDA_RESSUPRIMENTO_PEDIDO ADD QTD_TEMP NUMBER(13,3);
UPDATE ONDA_RESSUPRIMENTO_PEDIDO SET QTD_TEMP = QTD;
UPDATE ONDA_RESSUPRIMENTO_PEDIDO SET QTD = 0;
ALTER TABLE ONDA_RESSUPRIMENTO_PEDIDO MODIFY QTD NUMBER(13,3);
UPDATE ONDA_RESSUPRIMENTO_PEDIDO SET QTD = QTD_TEMP;
ALTER TABLE ONDA_RESSUPRIMENTO_PEDIDO DROP COLUMN QTD_TEMP;


-- TABELA PALETE_PRODUTO
ALTER TABLE PALETE_PRODUTO ADD QTD_TEMP NUMBER(13,3);
UPDATE PALETE_PRODUTO SET QTD_TEMP = QTD;
UPDATE PALETE_PRODUTO SET QTD = 0;
ALTER TABLE PALETE_PRODUTO MODIFY QTD NUMBER(13,3);
UPDATE PALETE_PRODUTO SET QTD = QTD_TEMP;
ALTER TABLE PALETE_PRODUTO DROP COLUMN QTD_TEMP;

ALTER TABLE PALETE_PRODUTO ADD QTD_ENDERECADA_TEMP NUMBER(13,3);
UPDATE PALETE_PRODUTO SET QTD_ENDERECADA_TEMP = QTD_ENDERECADA;
UPDATE PALETE_PRODUTO SET QTD_ENDERECADA = 0;
ALTER TABLE PALETE_PRODUTO MODIFY QTD_ENDERECADA NUMBER(13,3);
UPDATE PALETE_PRODUTO SET QTD_ENDERECADA = QTD_ENDERECADA_TEMP;
ALTER TABLE PALETE_PRODUTO DROP COLUMN QTD_ENDERECADA_TEMP;



-- TABELA PEDIDO_PRODUTO
ALTER TABLE PEDIDO_PRODUTO ADD QUANTIDADE_TEMP NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QUANTIDADE_TEMP = QUANTIDADE;
UPDATE PEDIDO_PRODUTO SET QUANTIDADE = 0;
ALTER TABLE PEDIDO_PRODUTO MODIFY QUANTIDADE NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QUANTIDADE = QUANTIDADE_TEMP;
ALTER TABLE PEDIDO_PRODUTO DROP COLUMN QUANTIDADE_TEMP;

ALTER TABLE PEDIDO_PRODUTO ADD QTD_ATENDIDA_TEMP NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QTD_ATENDIDA_TEMP = QTD_ATENDIDA;
UPDATE PEDIDO_PRODUTO SET QTD_ATENDIDA = 0;
ALTER TABLE PEDIDO_PRODUTO MODIFY QTD_ATENDIDA NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QTD_ATENDIDA = QTD_ATENDIDA_TEMP;
ALTER TABLE PEDIDO_PRODUTO DROP COLUMN QTD_ATENDIDA_TEMP;

ALTER TABLE PEDIDO_PRODUTO ADD QTD_CORTADA_TEMP NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QTD_CORTADA_TEMP = QTD_CORTADA;
UPDATE PEDIDO_PRODUTO SET QTD_CORTADA = 0;
ALTER TABLE PEDIDO_PRODUTO MODIFY QTD_CORTADA NUMBER(13,3);
UPDATE PEDIDO_PRODUTO SET QTD_CORTADA = QTD_CORTADA_TEMP;
ALTER TABLE PEDIDO_PRODUTO DROP COLUMN QTD_CORTADA_TEMP;



-- TABELA PRODUTO_EMBALAGEM
ALTER TABLE PRODUTO_EMBALAGEM ADD QTD_EMBALAGEM_TEMP NUMBER(13,3);
UPDATE PRODUTO_EMBALAGEM SET QTD_EMBALAGEM_TEMP = QTD_EMBALAGEM;
UPDATE PRODUTO_EMBALAGEM SET QTD_EMBALAGEM = 0;
ALTER TABLE PRODUTO_EMBALAGEM MODIFY QTD_EMBALAGEM NUMBER(13,3);
UPDATE PRODUTO_EMBALAGEM SET QTD_EMBALAGEM = QTD_EMBALAGEM_TEMP;
ALTER TABLE PRODUTO_EMBALAGEM DROP COLUMN QTD_EMBALAGEM_TEMP;

ALTER TABLE PRODUTO_EMBALAGEM ADD CAPACIDADE_PICKING_TEMP NUMBER(13,3);
UPDATE PRODUTO_EMBALAGEM SET CAPACIDADE_PICKING_TEMP = CAPACIDADE_PICKING;
UPDATE PRODUTO_EMBALAGEM SET CAPACIDADE_PICKING = 0;
ALTER TABLE PRODUTO_EMBALAGEM MODIFY CAPACIDADE_PICKING NUMBER(13,3);
UPDATE PRODUTO_EMBALAGEM SET CAPACIDADE_PICKING = CAPACIDADE_PICKING_TEMP;
ALTER TABLE PRODUTO_EMBALAGEM DROP COLUMN CAPACIDADE_PICKING_TEMP;



-- TABELA REABASTECIMENTO_MANUAL
ALTER TABLE REABASTECIMENTO_MANUAL ADD QTD_TEMP NUMBER(13,3);
UPDATE REABASTECIMENTO_MANUAL SET QTD_TEMP = QTD;
UPDATE REABASTECIMENTO_MANUAL SET QTD = 0;
ALTER TABLE REABASTECIMENTO_MANUAL MODIFY QTD NUMBER(13,3);
UPDATE REABASTECIMENTO_MANUAL SET QTD = QTD_TEMP;
ALTER TABLE REABASTECIMENTO_MANUAL DROP COLUMN QTD_TEMP;



-- TABELA RECEBIMENTO_CONFERENCIA
ALTER TABLE RECEBIMENTO_CONFERENCIA ADD QTD_CONFERIDA_TEMP NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_CONFERIDA_TEMP = QTD_CONFERIDA;
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_CONFERIDA = 0;
ALTER TABLE RECEBIMENTO_CONFERENCIA MODIFY QTD_CONFERIDA NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_CONFERIDA = QTD_CONFERIDA_TEMP;
ALTER TABLE RECEBIMENTO_CONFERENCIA DROP COLUMN QTD_CONFERIDA_TEMP;

ALTER TABLE RECEBIMENTO_CONFERENCIA ADD QTD_DIVERGENCIA_TEMP NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_DIVERGENCIA_TEMP = QTD_DIVERGENCIA;
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_DIVERGENCIA = 0;
ALTER TABLE RECEBIMENTO_CONFERENCIA MODIFY QTD_DIVERGENCIA NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_DIVERGENCIA = QTD_DIVERGENCIA_TEMP;
ALTER TABLE RECEBIMENTO_CONFERENCIA DROP COLUMN QTD_DIVERGENCIA_TEMP;

ALTER TABLE RECEBIMENTO_CONFERENCIA ADD QTD_AVARIA_TEMP NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_AVARIA_TEMP = QTD_AVARIA;
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_AVARIA = 0;
ALTER TABLE RECEBIMENTO_CONFERENCIA MODIFY QTD_AVARIA NUMBER(13,3);
UPDATE RECEBIMENTO_CONFERENCIA SET QTD_AVARIA = QTD_AVARIA_TEMP;
ALTER TABLE RECEBIMENTO_CONFERENCIA DROP COLUMN QTD_AVARIA_TEMP;



-- TABELA RECEBIMENTO_EMBALAGEM
ALTER TABLE RECEBIMENTO_EMBALAGEM ADD QTD_CONFERIDA_TEMP NUMBER(13,3);
UPDATE RECEBIMENTO_EMBALAGEM SET QTD_CONFERIDA_TEMP = QTD_CONFERIDA;
UPDATE RECEBIMENTO_EMBALAGEM SET QTD_CONFERIDA = 0;
ALTER TABLE RECEBIMENTO_EMBALAGEM MODIFY QTD_CONFERIDA NUMBER(13,3);
UPDATE RECEBIMENTO_EMBALAGEM SET QTD_CONFERIDA = QTD_CONFERIDA_TEMP;
ALTER TABLE RECEBIMENTO_EMBALAGEM DROP COLUMN QTD_CONFERIDA_TEMP;



-- TABELA RESERVA_ESTOQUE_PRODUTO
ALTER TABLE RESERVA_ESTOQUE_PRODUTO ADD QTD_RESERVADA_TEMP NUMBER(13,3);
UPDATE RESERVA_ESTOQUE_PRODUTO SET QTD_RESERVADA_TEMP = QTD_RESERVADA;
UPDATE RESERVA_ESTOQUE_PRODUTO SET QTD_RESERVADA = 0;
ALTER TABLE RESERVA_ESTOQUE_PRODUTO MODIFY QTD_RESERVADA NUMBER(13,3);
UPDATE RESERVA_ESTOQUE_PRODUTO SET QTD_RESERVADA = QTD_RESERVADA_TEMP;
ALTER TABLE RESERVA_ESTOQUE_PRODUTO DROP COLUMN QTD_RESERVADA_TEMP;