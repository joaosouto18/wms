/* 
 * SCRIPT PARA: Criar campos para especificação do percentual de liberação para recebimentos
 * DATA DE CRIAÇÃO: 23/05/2019 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('x-percentual-recebimento.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5', 'x-percentual-recebimento.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE USUARIO ADD (PERCENT_RECEB NUMBER(3,0))';
        EXECUTE IMMEDIATE 'ALTER TABLE PERFIL_USUARIO ADD (PERCENT_RECEB NUMBER(3,0))';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 