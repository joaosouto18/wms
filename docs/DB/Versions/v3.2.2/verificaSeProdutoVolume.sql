INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:movimentacao'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'consultar-produto'), 'Verifica se Produto é Composto ou Uninatário');