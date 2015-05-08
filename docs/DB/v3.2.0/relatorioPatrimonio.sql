insert into ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) values (SQ_ACAO_01.NEXTVAL, 'imprimir relatorio de patrimonio', 'imprimir-relatorio');

insert into RECURSO_ACAO (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
values (SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from RECURSO where NOM_RECURSO like 'expedicao:volume-patrimonio'), (select COD_ACAO from ACAO where NOM_ACAO like 'imprimir-relatorio'), 'gerar relatorio de volume patrimonio');