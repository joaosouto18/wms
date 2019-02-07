INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', '07-email-chamado.sql');


insert into parametro (cod_parametro, cod_contexto_parametro, dsc_parametro, dsc_titulo_parametro, ind_parametro_sistema, cod_tipo_atributo, dsc_valor_parametro)
    values (sq_parametro_01.nextval, (select cod_contexto_parametro from contexto_parametro where dsc_contexto_parametro = 'PARÂMETROS DO SISTEMA'), 'EMAIL_CHAMADOS', 'E-mail base para abertura de chamado', 'S', 'A', 'rodrigodantley@imperiumsistemas.com.br');

insert into recurso (dsc_recurso, cod_recurso, cod_recurso_pai, nom_recurso)
    values ('envio de e-mail', sq_recurso_01.nextval, 0, 'email');

insert into recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
    values (sq_recurso_acao_01.nextval, (select cod_recurso from recurso where nom_recurso = 'email'), (select cod_acao from acao where nom_acao = 'index'), 'Envio de e-mail para abertura de chamados');

