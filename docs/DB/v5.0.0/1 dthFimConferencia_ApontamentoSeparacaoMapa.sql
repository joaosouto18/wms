INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','1 dthFimConferencia_ApontamentoSeparacaoMapa.sql');

ALTER TABLE APONTAMENTO_SEPARACAO_MAPA
  ADD (DTH_FIM_CONFERENCIA DATE);