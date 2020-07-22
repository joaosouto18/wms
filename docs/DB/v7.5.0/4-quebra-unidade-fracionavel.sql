/* 
 * SCRIPT PARA: Incluir possibilidade de quebra por unidade fracionável
 * DATA DE CRIAÇÃO: 03/04/2019
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('4-quebra-unidade-fracionavel.sql', '7.5.0') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE') THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5.0', '4-quebra-unidade-fracionavel.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
      EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (QUEBRA_UNID_FRACIONAVEL CHAR(1) DEFAULT ''N'')';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 