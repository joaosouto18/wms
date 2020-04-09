/* 
 * SCRIPT PARA: Criação de usuário root do WMS
 * DATA DE CRIAÇÃO: 07/04/2020 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
    IDPESSOA NUMBER(8);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('xx-wms-root.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v7.13', 'xx-wms-root.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE USUARIO ADD (ROOT_USER NUMBER(1) DEFAULT 0 NOT NULL)';
        UPDATE USUARIO SET ROOT_USER = 0 WHERE ROOT_USER IS NULL;

        SELECT SQ_PESSOA_01.nextval INTO IDPESSOA FROM DUAL;
        SELECT SQ_PESSOA_01.nextval FROM DUAL;
        INSERT INTO PESSOA (COD_PESSOA, NOM_PESSOA, COD_TIPO_PESSOA, DTH_INCLUSAO, DTH_ULTIMA_ALTERACAO)
        VALUES (20587, 'WMS ROOT', 'F', SYSDATE, SYSDATE);
        INSERT INTO PESSOA_FISICA (COD_PESSOA, NUM_CPF) VALUES (20587, '99999999999');

        INSERT INTO USUARIO (COD_USUARIO, DSC_IDENTIFICACAO_USUARIO, DSC_SENHA_ACESSO, IND_ATIVO, COD_PAPEL_USUARIO, IND_SENHA_PROVISORIA, PERCENT_RECEB, ROOT_USER)
        VALUES (20587, 'wms_root', 'wms_root', 'S', null, 'S', null, 1);

        INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.nextval, 'Gerenciamento de Integrações', null, 'integracao:gerenciamento');
        INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
        VALUES (
                   SQ_RECURSO_ACAO_01.nextval,
                   (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'integracao:gerenciamento'),
                   (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'),
                   'Gerenciamento de Integrações'
               );

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 