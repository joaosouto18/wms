/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 01/06/2018
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.5', '10-integracao-recebimento-ERP.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO,COD_CONTEXTO_PARAMETRO,DSC_PARAMETRO,DSC_TITULO_PARAMETRO,IND_PARAMETRO_SISTEMA,COD_TIPO_ATRIBUTO,DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL,(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARÂMETROS DE INTEGRAÇÃO'),'ID_INTEGRACAO_RECEBIMENTO_ERP','ID Integração de Recebimento do ERP','S','A',null);
