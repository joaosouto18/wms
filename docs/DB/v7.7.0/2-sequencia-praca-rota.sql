/* 
 * SCRIPT PARA: Alterações de elementos para inclusão de sequencia de praça e rota
 * DATA DE CRIAÇÃO: 23/07/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('2-sequencia-praca-rota.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.7', '2-sequencia-praca-rota.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'ALTER TABLE ROTA ADD (NUM_SEQ NUMBER(3))';
    EXECUTE IMMEDIATE 'ALTER TABLE PRACA ADD (NUM_SEQ NUMBER(3))';
    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (IND_SEQ_ROTA_PRACA CHAR(1))';
    EXECUTE IMMEDIATE 'ALTER TABLE TR_PEDIDO ADD ( SEQ_ROTA NUMBER(8), SEQ_PRACA NUMBER(8))';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 