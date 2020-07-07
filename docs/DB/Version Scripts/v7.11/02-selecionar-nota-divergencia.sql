INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7.11.0', '02-selecionar-nota-divergencia.sql');

alter table nota_fiscal
    add ind_divergencia CHAR(1) default 'N' null;

update nota_fiscal set ind_divergencia = 'S' where cod_nota_fiscal in (
    select distinct cod_nota_fiscal from recebimento_conferencia where cod_nota_fiscal is not null);