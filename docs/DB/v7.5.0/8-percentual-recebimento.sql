/* 
 * SCRIPT PARA: Criar campos para especificação do percentual de liberação para recebimentos
 * DATA DE CRIAÇÃO: 23/05/2019 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('8-percentual-recebimento.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5', '8-percentual-recebimento.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE USUARIO ADD (PERCENT_RECEB NUMBER(3,0))';
        EXECUTE IMMEDIATE 'ALTER TABLE PERFIL_USUARIO ADD (PERCENT_RECEB NUMBER(3,0))';

        insert into parametro (cod_parametro,cod_contexto_parametro,dsc_parametro,dsc_titulo_parametro,ind_parametro_sistema,cod_tipo_atributo,dsc_valor_parametro)
        values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'RECEBIMENTO'), 'HABILITA_PERC_RECEB','Restringir por nível de acesso percentual de permissão para liberar recebimento','S','A','N');


/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 