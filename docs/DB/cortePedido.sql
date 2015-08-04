INSERT INTO recurso (dsc_recurso, cod_recurso, cod_recurso_pai, nom_recurso) VALUES ('Corte de Pedido', SQ_RECURSO_01.NEXTVAL, 0, 'expedicao:corte-pedido');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte-pedido'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Index');

INSERT INTO recurso_acao (cod_recurso_acao, cod_recurso, cod_acao, dsc_recurso_acao)
VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte-pedido'),
(SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'list'), 'Listar Pedidos');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
(SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte-pedido') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index')),
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0),
'Corte de Pedidos',
10, '#', '_self', 'S');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
0,
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0),
'Etiqueta de Separação',
10, '#', '_self', 'S');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, DSC_TARGET, SHOW)
VALUES (SQ_MENU_ITEM_01.NEXTVAL,
0,
(SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0),
'Ressuprimento',
10, '#', '_self', 'S');

UPDATE MENU_ITEM
   SET COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Ressuprimento'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0))
WHERE DSC_MENU_ITEM IN ('Gerar Onda de Ressuprimento','Gerenciar OS de Ressuprimento');


UPDATE MENU_ITEM SET NUM_PESO = 0 WHERE DSC_MENU_ITEM = 'Etiqueta de Separação'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0);
UPDATE MENU_ITEM SET NUM_PESO = 1 WHERE DSC_MENU_ITEM = 'Ressuprimento'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0);
UPDATE MENU_ITEM SET NUM_PESO = 2 WHERE DSC_MENU_ITEM = 'Ordem Carregamento'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0);
UPDATE MENU_ITEM SET NUM_PESO = 3 WHERE DSC_MENU_ITEM = 'Corte de Pedidos'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0);
UPDATE MENU_ITEM SET NUM_PESO = 4 WHERE DSC_MENU_ITEM = 'Expedição Mercadorias'
                     AND COD_PAI = (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE DSC_MENU_ITEM = 'Expedição' AND COD_PAI = 0);

UPDATE MENU_ITEM SET DSC_MENU_ITEM = 'Gerar Ressuprimento' WHERE DSC_MENU_ITEM = 'Gerar Onda de Ressuprimento';