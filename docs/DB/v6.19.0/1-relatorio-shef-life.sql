INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.19.0','1-relatorio-shef-life.sql');


alter table recebimento_andamento
add (cod_produto varchar2(32 byte),
dsc_grade varchar2(32 byte),
dth_validade DATE);

alter table recebimento_andamento add dias_shelf_life number(8,0);
alter table recebimento_andamento add qtd_conferida number(12,4);