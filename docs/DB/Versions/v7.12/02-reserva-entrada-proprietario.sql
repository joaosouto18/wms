/* 
 * SCRIPT PARA: Criar estrutura para identificar saldo de entrada reservado e efetivado
 * DATA DE CRIAÇÃO: 31/01/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('02-reserva-entrada-proprietario.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.11', '02-reserva-entrada-proprietario.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'CREATE TABLE RESERVA_ESTOQUE_PROPRIETARIO (
                            COD_RESERVA NUMBER(8) PRIMARY KEY ,
                            COD_PRODUTO VARCHAR(20) NOT NULL,
                            DSC_GRADE VARCHAR(10) NOT NULL,
                            QTD NUMBER(13,3) NOT NULL,
                            COD_PROPRIETARIO NUMBER(8) NOT NULL,
                            COD_RECEBIMENTO NUMBER(8) NOT NULL,
                            COD_NOTA_FISCAL NUMBER(8) NOT NULL,
                            IND_APLICADO CHAR(1) NOT NULL,
                            DTH_RESERVA DATE NOT NULL,
                            DTH_APLICACAO DATE,
                            COD_USUARIO_APLICACAO NUMBER(8)
                        )';

    EXECUTE IMMEDIATE 'CREATE SEQUENCE SQ_RES_ESTQ_PROP_01
                            INCREMENT BY 1
                            START WITH 1
                            MAXVALUE 999999999999999999999999999
                            MINVALUE 0
                            NOCYCLE
                            NOCACHE
                            NOORDER
                        ';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 