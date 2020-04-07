/* 
 * SCRIPT PARA: Estrutura para permitir cancelamento do inventário
 * DATA DE CRIAÇÃO: 16/03/2020 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('06-correcao-cancelamento-inventario.sql', '02-correcao-estrutura-inventario-novo.sql')
    INTO CHECK_RESULT
    FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
        VALUES (SYSDATE, '7.12', '06-correcao-cancelamento-inventario.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'ALTER TABLE INVENTARIO_CONT_END_OS ADD (IND_ATIVO CHAR(1) DEFAULT 1 NOT NULL )';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 