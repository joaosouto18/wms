/* 
 * SCRIPT PARA: Status de NFS cancelada
 * DATA DE CRIAÇÃO: 18/08/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('xx-cancelamento-nfs.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.xx.x', 'xx-cancelamento-nfs.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        INSERT INTO TIPO_SIGLA (COD_TIPO_SIGLA, DSC_TIPO_SIGLA, IND_SIGLA_SISTEMA) VALUES (SQ_TIPO_SIGLA_01.nextval, 'STATUS NOTA FISCAL SAIDA', 'S');
        INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA)
        VALUES (
                   564,
                   (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS NOTA FISCAL SAIDA'),
                   'CANCELADA',
                   'NFSC'
               );

        EXECUTE IMMEDIATE 'ALTER TABLE NOTA_FISCAL_SAIDA ADD (DTH_CANCELAMENTO DATE)';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 