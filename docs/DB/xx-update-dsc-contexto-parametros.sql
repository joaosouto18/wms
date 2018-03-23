/* 
 * SCRIPT PARA: Padronizar a descrição dos contextos dos parametros
 * DATA DE CRIAÇÃO: 23/03/2018 
 * CRIADO POR: tarci
 *
 */
DECLARE 
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('xx-update-dsc-contexto-parametros.sql', '') INTO CHECK_RESULT FROM DUAL ;
  IF (CHECK_RESULT <> 'TRUE') THEN 
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE 
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6','xx-update-dsc-contexto-parametros.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/
    Select TRANSLATE ('Testando a Função Translate - Retira Acentuação',
                      'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                      'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy')
      As TRANSLATE FROM DUAL;




    /************************************************************************
    **                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
    ************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 