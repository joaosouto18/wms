/*
 * SCRIPT PARA: Alterar no banco a coluna COD_CARGA_EXTERNO da tabela Carga para varchar
 * DATA DE CRIAÇÃO: 10/10/2018
 * CRIADO POR: tarci
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('08-cod-carga-externo-varchar.sql', '') INTO CHECK_RESULT FROM DUAL ;
  IF (CHECK_RESULT <> 'TRUE') THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6','08-cod-carga-externo-varchar.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'alter table carga add temp_cod_carga_externo varchar(20)';
        EXECUTE IMMEDIATE 'update carga set TEMP_COD_CARGA_EXTERNO = cod_carga_externo';
        EXECUTE IMMEDIATE 'alter table carga drop column cod_carga_externo';
        EXECUTE IMMEDIATE 'alter table carga add cod_carga_externo varchar(20)';
        EXECUTE IMMEDIATE 'update carga set cod_carga_externo = TEMP_COD_CARGA_EXTERNO';
        EXECUTE IMMEDIATE 'alter table carga drop column temp_cod_carga_externo';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;

