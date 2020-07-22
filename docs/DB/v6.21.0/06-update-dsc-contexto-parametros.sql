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
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.21.0','06-update-dsc-contexto-parametros.sql');
/************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'PARAMETROS DE INTEGRACAO';

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DO SISTEMA' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'PARAMETROS DO SISTEMA';

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'ENDEREÇAMENTO' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'ENDERECAMENTO';

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'RELATÓRIOS E IMPRESSÃO' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'RELATORIOS E IMPRESSAO';

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'EXPEDIÇÃO' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'EXPEDICAO';

    UPDATE CONTEXTO_PARAMETRO SET DSC_CONTEXTO_PARAMETRO = 'INVENTÁRIO' WHERE
      TRANSLATE (DSC_CONTEXTO_PARAMETRO,
                 'ŠŽšžŸÁÇÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕËÜÏÖÑÝåáçéíóúàèìòùâêîôûãõëüïöñýÿ',
                 'SZszYACEIOUAEIOUAEIOUAOEUIONYaaceiouaeiouaeiouaoeuionyy') = 'INVENTARIO';



    /************************************************************************
    **                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
    ************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 