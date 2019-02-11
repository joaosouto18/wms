/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 01/06/2018
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', '09-permissao-acesso');

INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Imprimir Etiquetas','gerar-etiqueta');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
VALUES  (SQ_RECURSO_ACAO_01.NEXTVAL,
(SELECT COD_RECURSO FROM RECURSO WHERE Nom_recurso = 'produto'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'gerar-etiqueta'),
'Imprimir Etiqueta do Produto');
