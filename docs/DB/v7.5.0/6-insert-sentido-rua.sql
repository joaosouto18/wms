/* 
 * SCRIPT PARA: Criar registros na tabela sentido rua
 * DATA DE CRIAÇÃO: 21/05/2019 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('6-insert-sentido-rua.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5', '6-insert-sentido-rua.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
        for i in (select distinct num_rua, cod_deposito from deposito_endereco  where num_rua not in (select num_rua from sentido_rua)) loop
            insert into sentido_rua (cod_sentido_rua, dsc_sentido_rua, num_rua, cod_deposito) values (SQ_SENTIDO_RUA_01.nextval, 'C', i.num_rua, i.cod_deposito);
        end loop;
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 