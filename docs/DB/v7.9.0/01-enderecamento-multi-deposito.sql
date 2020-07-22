/* 
 * SCRIPT PARA: Alterações para permitir endereçamentos orientados por deposito
 * DATA DE CRIAÇÃO: 16/09/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('01-enderecamento-multi-deposito.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.9', '01-enderecamento-multi-deposito.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE DEPOSITO ADD (IND_USA_ENDERECAMENTO CHAR(1))';
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
UPDATE DEPOSITO SET IND_USA_ENDERECAMENTO = 'S' WHERE IND_USA_ENDERECAMENTO IS NULL;
 
 