INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','11-inserir-embalagem-inativa.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
  values (SQ_PARAMETRO_01.NEXTVAL, 2, 'SALVAR_EMBALAGEM_COMO_ATIVA', 'Permitir Salvar Embalagens Inativas como Ativa', 'S', 'A', 'N');