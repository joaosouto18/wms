/* 
 * SCRIPT PARA: Criação dos parâmetros para disparar ou não integração de inicio da conferência no ERP
 * DATA DE CRIAÇÃO: 08/08/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('02-integracao-erp-inicio-conferencia.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
        VALUES (SYSDATE, '7.8', '02-integracao-erp-inicio-conferencia.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
        VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'IND_INFORMA_ERP_INICIO_CONFERENCIA','Notificar o ERP sobre o início da conferência (S/N)','S','A','N');

        INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
        VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'ID_INTEGRACAO_INFORMA_ERP_INICIO_CONFERENCIA','Id Integração Notificação do ERP sobre Conferencia','S','A', null);

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 