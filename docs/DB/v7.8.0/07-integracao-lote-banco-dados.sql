/* 
 * SCRIPT PARA: Alterar estrutura pra inclusão de totalizador de volumes por entrega
 * DATA DE CRIAÇÃO: 28/08/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('07-integracao-lote-banco-dados.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.8', '07-integracao-lote-banco-dados.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'alter table tr_nota_fiscal_entrada add (DSC_LOTE varchar2(64) null)';
         EXECUTE IMMEDIATE 'alter table integracao_nf_entrada add (DSC_LOTE varchar2(64) null)';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
-----------

--INTEGRAÇÃO CLIENTE

ALTER TABLE CLIENTE
ADD (COD_CLIENTE_EXTERNO_BKP VARCHAR2(64) );

UPDATE CLIENTE SET COD_CLIENTE_EXTERNO_BKP = COD_CLIENTE_EXTERNO;

UPDATE CLIENTE SET COD_CLIENTE_EXTERNO = NULL;

ALTER TABLE CLIENTE
MODIFY (COD_CLIENTE_EXTERNO VARCHAR2(64) );

UPDATE CLIENTE SET COD_CLIENTE_EXTERNO = COD_CLIENTE_EXTERNO_BKP;

ALTER TABLE CLIENTE
DROP COLUMN COD_CLIENTE_EXTERNO_BKP;