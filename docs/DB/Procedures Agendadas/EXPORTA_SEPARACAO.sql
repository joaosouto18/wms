create or replace PROCEDURE EXPORTA_SEPARACAO AS 
BEGIN

execute immediate 'DROP TABLE EXPORTACAO_SEPARACAO ';

execute immediate 'CREATE TABLE EXPORTACAO_SEPARACAO as
 SELECT EQ.DTH_VINCULO,
       ES.COD_ETIQUETA_SEPARACAO,
       PF.NUM_CPF AS CPF_SEPARADOR
  FROM EQUIPE_SEPARACAO EQ
  LEFT JOIN ETIQUETA_SEPARACAO ES ON ES.COD_ETIQUETA_SEPARACAO >= EQ.ETIQUETA_INICIAL 
                                 AND ES.COD_ETIQUETA_SEPARACAO <= EQ.ETIQUETA_FINAL
  LEFT JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = EQ.COD_USUARIO';

END EXPORTA_SEPARACAO;


BEGIN
    DBMS_SCHEDULER.CREATE_JOB (
            job_name => '"JOB_EXPORTA_SEPARACAO"',
            job_type => 'STORED_PROCEDURE',
            job_action => 'EXPORTA_SEPARACAO',
            number_of_arguments => 0,
            start_date => NULL,
            repeat_interval => 'FREQ=DAILY;BYDAY=MON,TUE,WED,THU,FRI,SAT,SUN;BYHOUR=4;BYMINUTE=0;BYSECOND=0',
            end_date => NULL,
            enabled => FALSE,
            auto_drop => FALSE,
            comments => '');

    DBMS_SCHEDULER.SET_ATTRIBUTE(
             name => '"JOB_EXPORTA_SEPARACAO"',
             attribute => 'logging_level', value => DBMS_SCHEDULER.LOGGING_OFF);

    DBMS_SCHEDULER.enable(
             name => '"JOB_EXPORTA_SEPARACAO"');
END;
