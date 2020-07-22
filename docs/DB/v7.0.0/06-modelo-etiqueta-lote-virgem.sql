/* 
 * SCRIPT PARA: Parametro de modelo e dimensões de etiqueta
 * DATA DE CRIAÇÃO: 08/01/2019 
 * CRIADO POR: tarcisio cesar
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('06-modelo-etiqueta-lote-virgem.sql', '') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE') THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.2', '06-modelo-etiqueta-lote-virgem.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
    VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'RELATÓRIOS E IMPRESSÃO'),'MODELO_ETIQUETA_LOTE','Modelo de etiqueta p/ Lote Interno','S','A','1');

    INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
    VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'RELATÓRIOS E IMPRESSÃO'),'TAMANHO_ETIQUETA_LOTE','Dimensões da etiqueta de Lote (X,Y)','S','A',null);


    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 