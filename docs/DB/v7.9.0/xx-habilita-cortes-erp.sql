INSERT INTO ACAO(COD_ACAO, DSC_ACAO, NOM_ACAO) VALUES (SQ_ACAO_01.NEXTVAL, 'Habilita Cortes no ERP', 'habilita-corte-erp');

INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:corte'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'habilita-corte-erp'), 'Habilita Cortes no ERP');

ALTER TABLE EXPEDICAO ADD(
  IND_HABILITADO_CORTE_ERP VARCHAR2(20 BYTE) default 'N'
);

ALTER TABLE EXPEDICAO_ANDAMENTO ADD(
  IND_ERRO_PROCESSADO VARCHAR2(20 BYTE) default 'N'
);
