/* 
 * SCRIPT PARA: Criar estrutura para conferência de carregamento
 * DATA DE CRIAÇÃO: 09/06/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('xx-conf-carregamento.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.15', 'xx-conf-carregamento.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        INSERT INTO ATIVIDADE (COD_ATIVIDADE, DSC_ATIVIDADE, COD_SETOR_OPERACIONAL) VALUES (19, 'CONF. CARREGAMENTO', 1);
        EXECUTE IMMEDIATE 'ALTER TABLE NOTA_FISCAL_SAIDA ADD (COD_CHAVE_ACESSO CHAR(44))';
        EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (TIPO_CONF_CARREG CHAR(1) DEFAULT ''N'' NOT NULL)';

        EXECUTE IMMEDIATE ' CREATE TABLE CONFERENCIA_CARREGAMENTO (
                COD_CONF_CARREG NUMBER(8,0) PRIMARY KEY,
                COD_EXPEDICAO NUMBER(8,0) NOT NULL,
                TIPO_CONF_CARREG CHAR(1) NOT NULL,
                COD_STATUS CHAR(1) NOT NULL,
                COD_USUARIO NUMBER(8,0) NOT NULL,
                DTH_INICIO DATE NOT NULL,
                DTH_FIM DATE
            )';

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_CONF_CARREG_01
                INCREMENT BY 1
                START WITH 1
                MAXVALUE 999999999999999999999999999
                MINVALUE 0
                NOCYCLE
                NOCACHE
                NOORDER';

        EXECUTE IMMEDIATE 'CREATE TABLE CONF_CARREG_CLIENTE (
                                COD_CONF_CARREG_CLI NUMBER(8,0) PRIMARY KEY,
                                COD_CONF_CARREG NUMBER(8,0) NOT NULL,
                                COD_CLIENTE NUMBER(8,0) NOT NULL
                            )';

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_CONF_CARREG_CLIENTE_01
                            INCREMENT BY 1
                            START WITH 1
                            MAXVALUE 999999999999999999999999999
                            MINVALUE 0
                            NOCYCLE
                            NOCACHE
                            NOORDER';

        EXECUTE IMMEDIATE 'CREATE TABLE CONF_CARREG_OS (
                                COD_CONF_CARREG_OS NUMBER(8,0) PRIMARY KEY,
                                COD_CONF_CARREG NUMBER(8,0) NOT NULL,
                                COD_OS NUMBER(8,0) NOT NULL
                            )';

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_CONF_CARREG_OS_01
                            INCREMENT BY 1
                            START WITH 1
                            MAXVALUE 999999999999999999999999999
                            MINVALUE 0
                            NOCYCLE
                            NOCACHE
                            NOORDER';

        EXECUTE IMMEDIATE 'CREATE TABLE CONF_CARREG_VOLUME (
                                COD_CONF_CARREG_VOL NUMBER(8,0) PRIMARY KEY,
                                COD_CONF_CARREG_OS NUMBER(8,0) NOT NULL,
                                COD_VOLUME NUMBER(8,0) NOT NULL,
                                IND_TIPO_VOLUME CHAR(2) NOT NULL,
                                DTH_CONFERENCIA DATE NOT NULL
                            )';

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_CONF_CARREG_VOL_01
                            INCREMENT BY 1
                            START WITH 1
                            MAXVALUE 999999999999999999999999999
                            MINVALUE 0
                            NOCYCLE
                            NOCACHE
                            NOORDER;';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 