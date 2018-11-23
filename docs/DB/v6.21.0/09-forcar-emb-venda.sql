/* 
 * SCRIPT PARA: Alterar banco pra aceitar embalagem padrão de venda
 * DATA DE CRIAÇÃO: 21/10/2018 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('09-forcar-emb-venda.sql', '') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7', '09-forcar-emb-venda.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'ALTER TABLE PRODUTO ADD (IND_FORCA_EMB_VENDA CHAR(1))';

    EXECUTE IMMEDIATE 'ALTER TABLE MODELO_SEPARACAO ADD (IND_FORCA_EMB_VENDA CHAR(1) DEFAULT ''N'')';

    EXECUTE IMMEDIATE 'ALTER TABLE TR_PEDIDO ADD ( FATOR_EMBALAGEM_VENDA NUMBER(20,10) )';

    EXECUTE IMMEDIATE 'ALTER TABLE PEDIDO_PRODUTO ADD ( FATOR_EMBALAGEM_VENDA NUMBER(20,10) )';

    EXECUTE IMMEDIATE 'UPDATE TR_PEDIDO SET FATOR_EMBALAGEM_VENDA = 1 WHERE FATOR_EMBALAGEM_VENDA iS NULL';

    EXECUTE IMMEDIATE 'UPDATE PEDIDO_PRODUTO SET FATOR_EMBALAGEM_VENDA = 1 WHERE FATOR_EMBALAGEM_VENDA iS NULL';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 