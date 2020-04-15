/* 
 * SCRIPT PARA: Criar coluna que identifica o propósito do bloqueio da OS
 * DATA DE CRIAÇÃO: 25/10/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('03-data-final-checkout.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.10', '03-data-final-checkout.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'alter table mapa_separacao_emb_cliente add DTH_CONF_CHECKOUT date null';
        EXECUTE IMMEDIATE 'alter table mapa_separacao_emb_cliente add COD_USUARIO_CONFERENCIA NUMBER null';
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 