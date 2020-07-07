/* 
 * SCRIPT PARA: Script de criação de estrutura para identificar a filial principal no contexto de multi depósito
 * DATA DE CRIAÇÃO: 28/01/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('01-filial-principal.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.11', '01-filial-principal.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE FILIAL ADD (IND_PRINCIPAL CHAR(1) DEFAULT ''N'' NOT NULL)';
        EXECUTE IMMEDIATE 'UPDATE FILIAL SET IND_PRINCIPAL = ''S'' WHERE COD_FILIAL IN (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = ''ID_USER_ERP'')';

        INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.nextval, 'Tornar Principal', 'tornar-principal');
        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
        VALUES (
                SQ_RECURSO_ACAO_01.nextval,
                (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'filial'),
                (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'tornar-principal'),
                'Alterar a filial principal'
                );

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;