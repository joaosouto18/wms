INSERT INTO RECURSO (DSC_RECURSO, COD_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES ('Consulta de validade', SQ_RECURSO_01.NEXTVAL, 0, 'validade:consulta');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)
  VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (select COD_RECURSO from recurso where NOM_RECURSO like 'validade:consulta'),
  (select COD_ACAO from acao where NOM_ACAO like 'index'), 'Consulta de validade');

insert into recurso (dsc_recurso, cod_recurso, cod_recurso_pai, nom_recurso) values ('Validade', SQ_RECURSO_01.NEXTVAL, 0, 'validade');
update RECURSO set COD_RECURSO_PAI = (select COD_RECURSO from recurso where NOM_RECURSO = 'validade') where COD_RECURSO = (select COD_RECURSO from recurso where NOM_RECURSO = 'validade:consulta');