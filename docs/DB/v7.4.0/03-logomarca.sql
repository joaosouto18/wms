/* INSERÇÃO DE LOGOMARCA DO CLIENTE */
INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.4.0','03-logomarca');

INSERT INTO acao (cod_acao, dsc_acao, nom_acao) VALUES (SQ_ACAO_01.NEXTVAL, 'Inserir logomarca', 'logomarca');

INSERT INTO recurso_acao ( cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao )
VALUES ( SQ_RECURSO_ACAO_01.NEXTVAL,
         (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'filial'),
         (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'logomarca'),
         'Adicionar logomarca'
           );

INSERT INTO perfil_usuario_recurso_acao (cod_perfil_usuario, COD_RECURSO_ACAO) VALUES (81, 1132);