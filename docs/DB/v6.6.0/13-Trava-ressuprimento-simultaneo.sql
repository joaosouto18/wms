/*
 * SCRIPT PARA: Impedir que multiplos usuários gerem onda de ressuprimento para a mesma expedição
 * DATA DE CRIAÇÃO: 29/01/2018 
 * CRIADO POR: Pichau
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('13-Trava-ressuprimento-simultaneo.sql', '')
  INTO CHECK_RESULT
  FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6', '13-Trava-ressuprimento-simultaneo.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    EXECUTE IMMEDIATE 'ALTER TABLE EXPEDICAO ADD IND_PROCESSANDO CHAR(1)';
    EXECUTE IMMEDIATE 'ALTER TABLE EXPEDICAO MODIFY IND_PROCESSANDO DEFAULT ''N''';
    EXECUTE IMMEDIATE 'UPDATE EXPEDICAO SET IND_PROCESSANDO = ''N'' WHERE IND_PROCESSANDO IS NULL';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 