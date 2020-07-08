/* 
 * SCRIPT PARA: Parâmetro que define se o ERP é obrigado à especificar o lote
 * DATA DE CRIAÇÃO: 23/05/2019 
 * CRIADO POR: tarci
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('9-modelo-relatorio-carregamento.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5', '9-modelo-relatorio-carregamento.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        insert into parametro (cod_parametro,cod_contexto_parametro,dsc_parametro,dsc_titulo_parametro,ind_parametro_sistema,cod_tipo_atributo,dsc_valor_parametro)
        values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'RELATORIOS E IMPRESSAO'), 'MODELO_RELATORIO_CARREGAMENTO','Modelo do relatório de Carregamento','S','A','1');


/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
**************
**********************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 