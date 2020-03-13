/* 
 * SCRIPT PARA: Estrutura para permitir sequenciamento de volumes embalados e não embalados, com ou sem uso de caixa padrão
 * DATA DE CRIAÇÃO: 21/02/2020 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('03-usa-caixa-embalado.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.12', '03-usa-caixa-embalado.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (USA_CAIXA_PADRAO CHAR(1))';
    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (TIPO_SEQ_VOLS CHAR(1))';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 