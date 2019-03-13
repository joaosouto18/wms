INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.0.0', '07-email-chamado.sql');

insert into recurso (dsc_recurso, cod_recurso, cod_recurso_pai, nom_recurso)
    values ('envio de e-mail', sq_recurso_01.nextval, 0, 'email');

insert into recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
    values (sq_recurso_acao_01.nextval, (select cod_recurso from recurso where nom_recurso = 'email'), (select cod_acao from acao where nom_acao = 'index'), 'Envio de e-mail para abertura de chamados');

