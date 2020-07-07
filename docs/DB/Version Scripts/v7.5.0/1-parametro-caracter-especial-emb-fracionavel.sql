/* 
 * SCRIPT PARA: Criação do parâmetro para edição de pedidos
 * DATA DE CRIAÇÃO: 02/04/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('1-parametro-caracter-especial-emb-fracionavel.sql', '7.5.0') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE') THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5.0', '1-parametro-caracter-especial-emb-fracionavel.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    insert into parametro (cod_parametro,cod_contexto_parametro,dsc_parametro,dsc_titulo_parametro,ind_parametro_sistema,cod_tipo_atributo,dsc_valor_parametro)
    values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'PARÂMETROS DE INTEGRAÇÃO'), 'CARACTERE_ESPECIAL_COD_BARRAS','Aplicar # no código de barras das embalagens fracionáveis','S','A','N');

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 