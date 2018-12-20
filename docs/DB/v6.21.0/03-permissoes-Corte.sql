INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.21.0','03-permissoes-Corte.sql');


INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Corte de Pedidos', 'corte-pedido');
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Corte de Produtos', 'corte-produto');
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Corte Total de Produtos', 'corte-total');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'corte-pedido'), 'Corte de Pedidos');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'corte-produto'), 'Corte de Produtos');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'corte-total'), 'Corte Total de Produtos');