INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '6.20.6','3-finalizacaoRessuprimentoDesktop.sql');

/* Acessos a ação de finalização do ressuprimento pelo desktop */
INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO)VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'expedicao:onda-ressuprimento'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'finalizar'), 'Efetivar onda de ressuprimento pelo desktop');

/* Siglas para melhorar o andamento do ressuprimento */
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (626, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS ANDAMENTO RESSUPRIMENTO'), 'GERADO', 'G');
INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA, COD_REFERENCIA_SIGLA) VALUES (627, (SELECT COD_TIPO_SIGLA FROM TIPO_SIGLA WHERE DSC_TIPO_SIGLA = 'STATUS ANDAMENTO RESSUPRIMENTO'), 'FINALIZADO', 'F');

/* Modificações na tabela para identificar se o ressuprimento foi finalizado no desktop ou coletor */
ALTER TABLE ONDA_RESSUPRIMENTO_OS ADD  (IND_TIPO_FINALIZACAO VARCHAR2 (128 BYTE));
ALTER TABLE RESSUPRIMENTO_ANDAMENTO MODIFY (DSC_OBSERVACAO VARCHAR2 (128 BYTE));

/* Gera os andamentos antigos de geração do ressuprimento */
INSERT INTO RESSUPRIMENTO_ANDAMENTO (NUM_SEQUENCIA, COD_ONDA_RESSUPRIMENTO_OS, COD_USUARIO, DTH_ANDAMENTO, COD_TIPO)
SELECT SQ_RESSU_ANDAMENTO.NEXTVAL as NUM_SEQUENCIA,
       OROS.COD_ONDA_RESSUPRIMENTO_OS,
       ORO.COD_USUARIO,
       ORO.DTH_CRIACAO as DTH_ANDAMENTO,
       626 as COD_TIPO
  FROM ONDA_RESSUPRIMENTO_OS OROS
  LEFT JOIN ONDA_RESSUPRIMENTO ORO ON ORO.COD_ONDA_RESSUPRIMENTO = OROS.COD_ONDA_RESSUPRIMENTO;

/* Gera os andamentos antigos de finalização do ressuprimento */
INSERT INTO RESSUPRIMENTO_ANDAMENTO (NUM_SEQUENCIA, COD_ONDA_RESSUPRIMENTO_OS, COD_USUARIO, DTH_ANDAMENTO, COD_TIPO, DSC_OBSERVACAO)
SELECT SQ_RESSU_ANDAMENTO.NEXTVAL as NUM_SEQUENCIA,
       OROS.COD_ONDA_RESSUPRIMENTO_OS,
       OS.COD_PESSOA as COD_USUARIO,
       OS.DTH_FINAL_ATIVIDADE as DTH_ANDAMENTO,
       627 as COD_TIPO,
       'Finalizado pelo Coletor' as  DSC_OBSERVACAO
  FROM ONDA_RESSUPRIMENTO_OS OROS
  LEFT JOIN ONDA_RESSUPRIMENTO ORO ON ORO.COD_ONDA_RESSUPRIMENTO = OROS.COD_ONDA_RESSUPRIMENTO
  LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = OROS.COD_OS
  WHERE OROS.COD_STATUS = 541;

/* Acerta a forma de finalização dos ressuprimentos ja finalizados */
UPDATE ORDEM_SERVICO SET COD_FORMA_CONFERENCIA = 'C' WHERE COD_OS IN (
SELECT COD_OS FROM ONDA_RESSUPRIMENTO_OS WHERE COD_STATUS = 541);

UPDATE ONDA_RESSUPRIMENTO_OS
   SET IND_TIPO_FINALIZACAO = 'C'
 WHERE COD_STATUS = 541;

/* Reordena os andamentos de ressuprimentos para que fiquem em da data do andamento */
BEGIN
    FOR i IN (SELECT NUM_SEQUENCIA
                FROM RESSUPRIMENTO_ANDAMENTO
               ORDER BY DTH_ANDAMENTO)
    LOOP
        UPDATE RESSUPRIMENTO_ANDAMENTO
           SET NUM_SEQUENCIA = SQ_RESSU_ANDAMENTO.NEXTVAL
         WHERE NUM_SEQUENCIA = i.NUM_SEQUENCIA;
    END LOOP;
END;