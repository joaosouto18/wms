/*
 * SCRIPT PARA: Criar tabela e constrains pra quebra na separação de embalados
 * DATA DE CRIAÇÃO: 06/02/2018 
 * CRIADO POR: Pichau
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('12-quebras-separacao-embalados.sql', '')
  INTO CHECK_RESULT
  FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.13.0', '12-quebras-separacao-embalados.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

  EXECUTE IMMEDIATE 'CREATE TABLE MODELO_SEPARACAO_TPQUEB_EMB (
                            COD_MODELO_SEPARACAO NUMBER(5,0),
                            IND_TIPO_QUEBRA CHAR(1)
                            ) LOGGING';

    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO_TPQUEB_EMB ADD CONSTRAINT MOD_SEPARACAO_TPQUEB_EMB_PK
                            PRIMARY KEY ( COD_MODELO_SEPARACAO, IND_TIPO_QUEBRA )';

    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO_TPQUEB_EMB ADD CONSTRAINT MOD_SEP_TPQUEB_EMB_FK
                            FOREIGN KEY ( COD_MODELO_SEPARACAO )
                            REFERENCES MODELO_SEPARACAO ( COD_MODELO_SEPARACAO ) ON
                              DELETE CASCADE ';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 