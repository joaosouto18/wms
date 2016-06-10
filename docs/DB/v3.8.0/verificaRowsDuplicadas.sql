SELECT R.COD_RECURSO, A.COD_ACAO FROM RECURSO R
INNER JOIN RECURSO_ACAO RA ON RA.COD_RECURSO = R.COD_RECURSO
INNER JOIN ACAO A ON RA.COD_ACAO = A.COD_ACAO
HAVING COUNT(R.COD_RECURSO) > 1 AND COUNT(A.COD_ACAO) > 1
GROUP BY R.COD_RECURSO, A.COD_ACAO;

/*
  IMPORTANTE: ANTES DE DELETAR O RECURSO DUPLICADO, É NECESSARIO VERIFICAR A TABELA MENU_ITEM E TER CERTEZA
  QUE O COD_RECURSO_ACAO NÃO ESTÁ SENDO USADO
*/