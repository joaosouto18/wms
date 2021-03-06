INSERT INTO RECURSO (COD_RECURSO, DSC_RECURSO, COD_RECURSO_PAI, NOM_RECURSO) VALUES (SQ_RECURSO_01.NEXTVAL, 'Equipe de Carregamento', 0, 'produtividade:carregamento');
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'produtividade:carregamento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'index'), 'Vincular Equipe de Carregamento');

INSERT INTO MENU_ITEM (COD_MENU_ITEM, COD_RECURSO_ACAO, COD_PAI, DSC_MENU_ITEM, NUM_PESO, DSC_URL, SHOW) VALUES (SQ_MENU_ITEM_01.NEXTVAL, (SELECT COD_RECURSO_ACAO FROM RECURSO_ACAO WHERE COD_RECURSO = (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'produtividade:carregamento') AND COD_ACAO = '5'), 49, 'Vincular Equipe de Carregamento', 1, '#', 'N');
INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL,
(SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'EXPEDICAO'),
'VINCULA_EQUIPE_CARREGAMENTO',
'Vincula Equipe de Carregamento',
'N',
'A',
'S');


CREATE TABLE EQUIPE_CARREGAMENTO_EXPEDICAO
(
  COD_EQUIPE_CARREGAMENTO  NUMBER (8) NOT NULL ,
  COD_EXPEDICAO            NUMBER (8) NOT NULL ,
  COD_USUARIO              NUMBER (8) NOT NULL ,
  DTH_VINCULO              DATE
);

ALTER TABLE EQUIPE_CARREGAMENTO_EXPEDICAO ADD CONSTRAINT EXP_CARREG_PK PRIMARY KEY ( COD_EQUIPE_CARREGAMENTO ) ;
ALTER TABLE EQUIPE_CARREGAMENTO_EXPEDICAO ADD CONSTRAINT EXP_CARREG_FK FOREIGN KEY ( COD_EXPEDICAO ) REFERENCES EXPEDICAO ( COD_EXPEDICAO ) NOT DEFERRABLE ;
ALTER TABLE EQUIPE_CARREGAMENTO_EXPEDICAO ADD CONSTRAINT EXP_CARREG_USU_FK FOREIGN KEY ( COD_USUARIO ) REFERENCES USUARIO ( COD_USUARIO ) NOT DEFERRABLE ;

CREATE SEQUENCE SQ_EQUIPE_CARREG_01
START WITH 1
MAXVALUE 99999999999999999
MINVALUE 1
NOCYCLE
NOCACHE
NOORDER;

INSERT INTO "PERFIL_USUARIO" (COD_PERFIL_USUARIO, DSC_PERFIL_USUARIO, NOM_PERFIL_USUARIO) VALUES (SQ_PERFIL_USUARIO_01.NEXTVAL, 'Equipe de Carregamento', 'EQP.CARREGAMENTO');
