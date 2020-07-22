/* 
 * SCRIPT PARA: Alterações de banco para permitir especificação do total de volumes ao final da conferência do checkout
 * DATA DE CRIAÇÃO: 16/07/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('1-criar-volumes-final-do-checkout-atividade-embalado.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
        VALUES (SYSDATE, '7.7', '1-criar-volumes-final-do-checkout-atividade-embalado.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (CRIAR_VOLS_FINAL_CHECKOUT CHAR(1))';

        EXECUTE IMMEDIATE 'ALTER TABLE MAPA_SEPARACAO_EMB_CLIENTE ADD (COD_OS NUMBER(8))';

        EXECUTE IMMEDIATE 'ALTER TABLE MAPA_SEPARACAO_EMB_CLIENTE ADD CONSTRAINT FK_MSEC_OS FOREIGN KEY ( COD_OS ) REFERENCES ORDEM_SERVICO ( COD_OS )';

        INSERT INTO ATIVIDADE (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES (18, 'EMBALAGEM EXPEDIÇÃO', 1);

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;