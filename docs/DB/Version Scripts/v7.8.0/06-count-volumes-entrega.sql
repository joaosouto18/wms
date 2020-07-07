/* 
 * SCRIPT PARA: Alterar estrutura pra inclusão de totalizador de volumes por entrega
 * DATA DE CRIAÇÃO: 28/08/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('06-count-volumes-entrega.sql', '2-agrupa-cont-etiquetas.sql') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.8', '06-count-volumes-entrega.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE ETIQUETA_SEPARACAO ADD (POS_ENTREGA NUMBER(8), TOTAL_ENTREGA NUMBER(8))';
        EXECUTE IMMEDIATE 'ALTER TABLE MAPA_SEPARACAO_EMB_CLIENTE ADD (POS_ENTREGA NUMBER(8), TOTAL_ENTREGA NUMBER(8))';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 