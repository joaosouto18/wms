INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '4.0.0', 'tempoDeInatividade.sql');

INSERT INTO PARAMETRO (COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO, DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (SQ_PARAMETRO_01.NEXTVAL,
        (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO = 'PARAMETROS DO SISTEMA'),
        'TEMPO_INATIVIDADE',
        'Tempo máximo de inatividade no sistema (Minutos)',
        'N',
        'A',
        '60');