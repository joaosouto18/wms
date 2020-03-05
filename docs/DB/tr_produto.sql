INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'x.xx', 'xx-tr_produto.sql');

alter table tr_produto add REF_FORNECEDOR varchar2(64);

insert into acao values (sq_acao_01.nextval,'Reimprimir etiquetas embalados','reimprimir-embalado-unico');
insert into recurso_acao values (sq_recurso_acao_01.nextval,(select cod_recurso from recurso where nom_recurso = 'expedicao:etiqueta'), (select cod_acao from acao where nom_acao = 'reimprimir-embalado-unico'), 'reimprimir etiquetas embalados');

insert into acao values (sq_acao_01.nextval,'Produtos por volume embalado','produtos-volumes-embalados');
insert into recurso_acao values (sq_recurso_acao_01.nextval,(select cod_recurso from recurso where nom_recurso = 'expedicao:os'), (select cod_acao from acao where nom_acao = 'produtos-volumes-embalados'),'Listar produtos inclusos volumes embalados');