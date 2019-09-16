/* 
 * SCRIPT PARA: Alterações de elementos para inclusão de sequencia de praça e rota
 * DATA DE CRIAÇÃO: 23/07/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('05-tipo-pedido.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.8', '05-tipo-pedido.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'alter table integracao_pedido add (tipo_pedido VARCHAR2(20 BYTE))';
    EXECUTE IMMEDIATE 'alter table integracao_pedido add (nom_motorista VARCHAR2(200 BYTE))';



/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;

Insert into PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
    values (sq_parametro_01.nextval,'2','TIPO_INTEGRACAO_CORTE','Momento que Dispara o Corte para o ERP (Instantâneo(I)/Finalização(F))','S','A',null);


 alter table expedicao_andamento add (
     ind_erro_processado char(1 byte)
);