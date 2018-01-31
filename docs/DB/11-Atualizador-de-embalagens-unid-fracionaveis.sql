/* 
 * SCRIPT PARA: Este script deve ser executado apenas em clientes que forem utilizar unidade fracionavel, o mesmo depende que alimente a tabela temporária TEMP_EMB com os produtos à serem atualizados
 * DATA DE CRIAÇÃO: 31/01/2018 
 * CRIADO POR: tarci
 *
 */
DECLARE 
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('11-Atualizador-de-embalagens-unid-fracionaveis.sql', '10-unidade-fracionavel.sql') INTO CHECK_RESULT FROM DUAL ;
  IF (CHECK_RESULT <> 'TRUE') THEN 
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE 
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.12.0','11-Atualizador-de-embalagens-unid-fracionaveis.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

  EXECUTE IMMEDIATE 'CREATE TABLE TEMP_EMB (
    COD_PRODUTO VARCHAR2(100),
    COD_BARRAS VARCHAR2(10))';

    DELETE FROM PRODUTO_EMBALAGEM WHERE IND_PADRAO = 'N' AND COD_PRODUTO IN (
      SELECT PE.COD_PRODUTO FROM PRODUTO_EMBALAGEM PE
        INNER JOIN TEMP_EMB TE ON TE.COD_PRODUTO = PE.COD_PRODUTO
      WHERE PE.DTH_INATIVACAO IS NULL GROUP BY PE.COD_PRODUTO HAVING COUNT(PE.COD_PRODUTO) > 1);

    BEGIN
      FOR I IN (SELECT COD_PRODUTO FROM TEMP_EMB WHERE COD_PRODUTO = '657177') LOOP
        UPDATE PRODUTO SET
          IND_FRACIONAVEL = 'S',
          UNID_FRACAO = 'M',
          IND_POSSUI_PESO_VARIAVEL = 'N',
          PERC_TOLERANCIA = NULL,
          TOLERANCIA_NOMINAL = NULL
        WHERE COD_PRODUTO = I.COD_PRODUTO;

        UPDATE PRODUTO_EMBALAGEM SET
          DSC_EMBALAGEM = 'M',
          IND_PADRAO = 'S',
          QTD_EMBALAGEM = 1,
          IND_CB_INTERNO = 'N',
          IND_IMPRIMIR_CB = 'S',
          IS_EMB_FRACIONAVEL_DEFAULT = 'S',
          IS_EMB_EXPEDICAO_DEFAULT = 'N'
        WHERE COD_PRODUTO = I.COD_PRODUTO AND (IS_EMB_FRACIONAVEL_DEFAULT = 'N' OR IS_EMB_FRACIONAVEL_DEFAULT IS NULL);
      END LOOP;
    END;

    EXECUTE IMMEDIATE 'DROP TABLE TEMP_EMB';
  
/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 