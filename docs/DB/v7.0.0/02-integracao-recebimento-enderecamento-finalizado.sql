/* 
 * SCRIPT PARA: Insert de parâmetros de configuração para integração de script de notificação ao erp ao finalizar o recebimento e endereçamento liberando notas para faturamento
 * DATA DE CRIAÇÃO: 02/10/2018 
 * CRIADO POR: tarci
 *
 */
DECLARE 
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('02-integracao-recebimento-enderecamento-finalizado.sql', '') INTO CHECK_RESULT FROM DUAL ;

  IF (CHECK_RESULT <> 'TRUE') THEN 

    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE 
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7','02-integracao-recebimento-enderecamento-finalizado.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    INSERT INTO SIGLA (COD_SIGLA,COD_TIPO_SIGLA,DSC_SIGLA) VALUES (628,79,'LIBERA FATURAMENTO NF ERP');
    INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
      VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'ID_INTEGRACAO_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP','ID Integração de liberação de faturamento de NF (recebimento) no ERP','S','A',null);

    INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
      VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'IND_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP','Integração de liberação de faturamento de NF (recebimento) no ERP (S/N)','S','A','N');

    INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
      VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'FORMATO_DATA_ERP','Padrão de formato para DATA no ERP','S','A',null);

    /************************************************************************
    **                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
    ************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 