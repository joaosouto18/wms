UPDATE DEPOSITO_ENDERECO SET IND_DISPONIVEL = 'S';

UPDATE DEPOSITO_ENDERECO SET IND_DISPONIVEL = 'N' WHERE COD_DEPOSITO_ENDERECO IN (
SELECT COD_DEPOSITO_ENDERECO
FROM ESTOQUE
UNION
SELECT COD_DEPOSITO_ENDERECO
FROM RESERVA_ESTOQUE_ENDERECAMENTO REE
INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
WHERE RE.IND_ATENDIDA = 'N');