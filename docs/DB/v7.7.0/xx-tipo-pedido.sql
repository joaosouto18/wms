/* 
 * SCRIPT PARA: Alterações de elementos para inclusão de sequencia de praça e rota
 * DATA DE CRIAÇÃO: 23/07/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('xx-tipo-pedido.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.7', 'xx-tipo-pedido.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'alter table integracao_pedido add (tipo_pedido VARCHAR2(20 BYTE))';
    EXECUTE IMMEDIATE 'alter table integracao_pedido add (nom_motorista VARCHAR2(200 BYTE))';



/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 