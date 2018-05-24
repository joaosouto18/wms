/* 
 * SCRIPT PARA: Criar permissão de acesso à funcionalidade de impressão do relatório de volumes de embalados
 * DATA DE CRIAÇÃO: 17/04/2018 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('06-permissao-relatorio-volume-embalado.sql', '')
  INTO CHECK_RESULT
  FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE')
  THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
    VALUES (SYSDATE, '6.17', '06-permissao-relatorio-volume-embalado.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, ''Relatório de volumes embalados'', ''relatorio-itens-volume-embalado'')';
        EXECUTE IMMEDIATE 'INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
                              VALUES (SQ_RECURSO_ACAO_01.NEXTVAL,
                                      (SELECT COD_RECURSO FROM RECURSO  WHERE NOM_RECURSO = ''expedicao:mapa''),
                                      (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = ''relatorio-itens-volume-embalado''), ''Relatório de volumes embalados'')';

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 