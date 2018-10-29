INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.22.0','01-importacao-estoque-erp.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'TIPO_INTEGRACAO_ESTOQUE_ERP', 'Tipo de Integração de Estoque com o ERP (txt/WebService)','S','A','WebService');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'INVENTÁRIO'),'MODELO_EXPORTACAO_INVENTARIO', 'Formato de Exportação do Inventário (1 - Padrão, 2 - TXT Simplificado)','S','A','1');

/*
  DAQUI PARA BAIXO É O LAYOUT DE IMPORTAÇÃO DO ESTOQUE DO ERP ATRAVÉS DE ARQUIVO TEXTO, NÃO É NECESSÁRIO RODAR EM TODOS OS CLIENTES
 */
INSERT INTO importacao_arquivo (cod_importacao_arquivo, tabela_destino, nome_arquivo, caracter_quebra, cabecalho, sequencia, ind_ativo, ultima_importacao, nom_input) VALUES
(SQ_IMPORTACAO_ARQUIVO_01.NEXTVAL, 'estoqueErp', 'invent.inv',';', 'N', 1,'S',null,'Estoque');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp') , 'COD_PRODUTO', 0, null, null, null, 'S');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'GRADE', null, null, null, 'UNICA', 'N');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'ESTOQUE_DISPONIVEL', 1, null, null, null, 'S');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'ESTOQUE_AVARIA', null, null, null, '0', 'N');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'ESTOQUE_GERENCIAL', 1, null, null, null, 'S');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'FATOR_UNIDADE_VENDA', null, null, null,  '1', 'N');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'DSC_UNIDADE', null, null, null, 'UN', 'N');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'VALOR_ESTOQUE', null, null, null, '0', 'N');

INSERT INTO importacao_campos (cod_importacao_campos, cod_importacao_arquivo, nome_campo, posicao_txt, tamanho_inicio, tamanho_fim, valor_padrao, preench_obrigatorio) VALUES
(SQ_IMPORTACAO_CAMPOS_01.NEXTVAL, (SELECT COD_IMPORTACAO_ARQUIVO FROM IMPORTACAO_ARQUIVO WHERE TABELA_DESTINO = 'estoqueErp'), 'CUSTO_UNITARIO', 2, null, null, null, 'N');
