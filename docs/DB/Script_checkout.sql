/* 
 * SCRIPT PARA: Functions para fazer as validações na execução dos scripts
 * DATA DE CRIAÇÃO: 03/01/2018 
 * CRIADO POR: tarci
 *
 */

--Configuração para exibir resultado do script
set serveroutput on format wrapped;

-- Criação do test_type que simula uma tabela
CREATE OR REPLACE TYPE TEMP_STR_TABLE AS TABLE OF VARCHAR2(1000);

-- Função que alimenta o test_type com a quebra da string por virgula
CREATE OR REPLACE FUNCTION f_convert(p_list IN VARCHAR2)
  RETURN TEMP_STR_TABLE
AS
  l_string       VARCHAR2(32767) := p_list || ',';
  l_comma_index  PLS_INTEGER;
  l_index        PLS_INTEGER := 1;
  l_tab          TEMP_STR_TABLE := TEMP_STR_TABLE();
  BEGIN
    LOOP
      l_comma_index := INSTR(l_string, ',', l_index);
      EXIT WHEN l_comma_index = 0;
      l_tab.EXTEND;
      l_tab(l_tab.COUNT) := TRIM(SUBSTR(l_string, l_index, l_comma_index - l_index));
      l_index := l_comma_index + 1;
    END LOOP;
    RETURN l_tab;
  END f_convert;

-- Criação da função que irá fazer os testes necessários para rodar os scripts
CREATE OR REPLACE FUNCTION FUNC_CHECK_SCRIPT ( SCRIPT_NAME IN VARCHAR2, SCRIPT_LIST IN VARCHAR2 )
  RETURN VARCHAR2 AS

  SCRIPTS_MISSING VARCHAR2(10000);
  DATE_RUN_SCRIPT DATE;

  BEGIN
    SELECT DTH INTO DATE_RUN_SCRIPT FROM VERSAO WHERE SCRIPT = SCRIPT_NAME;

    IF (DATE_RUN_SCRIPT IS NOT NULL) THEN
      --Caso já tenha executado este script
      RETURN 'Este script "' || SCRIPT_NAME || '" já foi executado';
    END IF;

    IF (SCRIPT_LIST IS NOT NULL OR SCRIPT_LIST <> '') THEN
      SELECT LISTAGG(SCRIPT_SEARCHED,',') WITHIN GROUP (ORDER BY SCRIPT_SEARCHED) ONDA INTO SCRIPTS_MISSING
      FROM ( SELECT SCRIPT_SEARCHED FROM (
        SELECT COLUMN_VALUE AS SCRIPT_SEARCHED
        FROM TABLE(F_CONVERT(SCRIPT_LIST)))
      WHERE SCRIPT_SEARCHED NOT IN (SELECT SCRIPT FROM VERSAO));

      IF (SCRIPTS_MISSING IS NOT NULL) THEN
        --Caso algum tenha algum script de pré requisito não executado
        RETURN 'O(s) script(s) "' || SCRIPTS_MISSING || '", é pré requisito deste "' || SCRIPT_NAME || '"!';
      END IF;
    END IF;
  RETURN 'TRUE';
END FUNC_CHECK_SCRIPT;

 
 