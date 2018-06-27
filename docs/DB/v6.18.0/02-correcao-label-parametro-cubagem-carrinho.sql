/* 
 * SCRIPT PARA: Corrigir a descrição do parametro de cubagem do carrinho de separação
 * DATA DE CRIAÇÃO: 12/04/2018 
 * CRIADO POR: tarci
 *
 */
DECLARE 
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('02-correcao-label-parametro-cubagem-carrinho.sql', '') INTO CHECK_RESULT FROM DUAL ;
  IF (CHECK_RESULT <> 'TRUE') THEN 
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE 
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.18.0','02-correcao-label-parametro-cubagem-carrinho.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
  
    EXECUTE IMMEDIATE 'UPDATE PARAMETRO SET DSC_TITULO_PARAMETRO = ''Cubagem em m³ da caixa no carrinho de separação'' WHERE DSC_PARAMETRO = ''CUBAGEM_CAIXA_CARRINHO''';
  
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 