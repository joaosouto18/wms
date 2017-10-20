/**

  ############################################################
  ##################                        ##################
  ##################         ATENÇÃO        ##################
  ##################                        ##################
  ############################################################

  Para rodar esse script:
  Ir até a pasta wms/library/Wms/WebService/
  no Arquivo NotaFiscal.php método Salvar
  na documentação do parâmetro $itens
  pegar o tipo e atribuir no valor deste script

 */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.5.0','2-parametrizacao-nota-fiscal-salvar-itens.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'PARAMETROS DE INTEGRACÃO'), 'NOTA_FISCAL_SALVAR_ITENS','Tipo esperado no parametro Itens, (NotaFiscal:Salvar)','S','A', null);