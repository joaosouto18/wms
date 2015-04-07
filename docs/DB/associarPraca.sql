INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Cliente', 0, 'expedicao:cliente');
INSERT INTO ACAO (COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Associar Praça', 'associar-praca');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:cliente'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'associar-praca'), 'Associar Praça');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, 0, (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Cadastros'), 'Cliente', 10, '#', 'S');
INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL,SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:cliente') AND COD_ACAO = (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'associar-praca')), (SELECT COD_MENU_ITEM FROM MENU_ITEM WHERE COD_PAI = 0 AND DSC_MENU_ITEM = 'Cliente'), 'Associar Praça', 10, '#', 'S');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:cliente'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar'), 'Detalhe Endereço Praça');

ALTER TABLE CLIENTE ADD (COD_PRACA NUMBER (8));
ALTER TABLE CLIENTE ADD CONSTRAINT CLIENTE_PRACA_FK FOREIGN KEY ( COD_PRACA ) REFERENCES PRACA ( COD_PRACA ) NOT DEFERRABLE ;