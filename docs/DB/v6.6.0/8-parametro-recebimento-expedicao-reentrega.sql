/* 
 * SCRIPT PARA: Inclui o parametro que define se ira criar uma expedicao imediatamente no recebimento de uma reentrega
 * DATA DE CRIAÇÃO: 09/01/2018 
 * CRIADO POR: tarci
 *
 */
DECLARE 
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('8-parametro-recebimento-expedicao-reentrega.sql', '') INTO CHECK_RESULT FROM DUAL ;
  IF (CHECK_RESULT <> 'TRUE') THEN 
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE 
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6','8-parametro-recebimento-expedicao-reentrega.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
    VALUES (SQ_PARAMETRO_01.NEXTVAL, (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'EXPEDICAO'),
            'IND_REENTREGA_RECEB_TO_EXP', 'Criar expedição de reentrega já no recebimento','N','A','N');
  
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 