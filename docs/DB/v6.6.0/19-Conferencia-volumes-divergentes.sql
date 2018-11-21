/* 
 * SCRIPT PARA: Criar na tabela RECEBIMENTO_CONFERENCIA campo para flag que identifica se o item tem ou não divergencia entre as quantidades dos volumes
 * DATA DE CRIAÇÃO: 02/03/2018 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('19-Conferencia-volumes-divergentes.sql', '')
  INTO CHECK_RESULT
  FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6', '19-Conferencia-volumes-divergentes.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

      EXECUTE IMMEDIATE 'ALTER TABLE RECEBIMENTO_CONFERENCIA ADD (IND_DIVERG_VOLUMES CHAR(1))';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 