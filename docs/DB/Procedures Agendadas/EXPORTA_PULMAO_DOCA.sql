create or replace PROCEDURE EXPORTA_PULMAO_DOCA AS
BEGIN

execute immediate 'DROP TABLE EXPORTACAO_PULMAO_DOCA ';

execute immediate 'CREATE TABLE EXPORTACAO_PULMAO_DOCA as
select DISTINCT
  c.cod_expedicao as expedicao,
  es.dth_separacao as data,
  de.dsc_deposito_endereco as endereco_origem,
  es.cod_produto as id_produto,
  p.dsc_produto as produto,
  es.dsc_grade as grade,
  count( distinct cod_etiqueta_separacao) qtd_etiquetas,
  us.DSC_IDENTIFICACAO_USUARIO login,
  pf.NUM_CPF,
  max(nvl(npv.num_norma, npe.num_norma)) norma,
  count(distinct nvl(pv.cod_norma_paletizacao, es.cod_produto_embalagem)) count_norma,
  count(distinct nvl(es.cod_produto_volume, es.cod_produto_embalagem)) count_volume
  ,((count( distinct cod_etiqueta_separacao) / count(distinct nvl(es.cod_produto_volume, es.cod_produto_embalagem))) * max(NVL(es.qtd_embalagem,1))) as num_itens
  ,CEIL(((count( distinct cod_etiqueta_separacao) / count(distinct nvl(es.cod_produto_volume, es.cod_produto_embalagem))) * max(NVL(es.qtd_embalagem,1))) / max(nvl(npv.num_norma, npe.num_norma))) as total_paletes
from etiqueta_separacao es
inner join deposito_endereco de on de.cod_deposito_endereco = es.cod_deposito_endereco
inner join produto p on p.cod_produto = es.cod_produto and p.dsc_grade = es.dsc_grade
left join produto_volume pv on pv.cod_produto_volume = es.cod_produto_volume
left join norma_paletizacao npv on npv.cod_norma_paletizacao = pv.cod_norma_paletizacao
left join produto_embalagem pe on pe.cod_produto_embalagem = es.cod_produto_embalagem
left join produto_dado_logistico pdl on pdl.cod_produto_embalagem = pe.cod_produto_embalagem
left join norma_paletizacao npe on npe.cod_norma_paletizacao = pdl.cod_norma_paletizacao and npe.IND_PADRAO = ''S''
left join pessoa_fisica pf on pf.cod_pessoa = es.cod_usuario_separacao
left join usuario us on us.cod_usuario = es.cod_usuario_separacao
inner join pedido ped on ped.cod_pedido = es.cod_pedido
inner join carga c on c.cod_carga = ped.cod_carga
where tipo_saida = 3 group by c.cod_expedicao, es.dth_separacao, nvl(npv.cod_norma_paletizacao, npe.cod_norma_paletizacao), de.dsc_deposito_endereco, es.cod_produto, p.dsc_produto, es.dsc_grade, us.DSC_IDENTIFICACAO_USUARIO, pf.NUM_CPF, pf.NUM_MATRICULA_EMPREGO
having max(nvl(npv.num_norma, npe.num_norma)) > 0
';

END EXPORTA_PULMAO_DOCA;


BEGIN
    DBMS_SCHEDULER.CREATE_JOB (
            job_name => '"JOB_EXPORTA_PULAO_DOCA"',
            job_type => 'STORED_PROCEDURE',
            job_action => 'EXPORTA_PULMAO_DOCA',
            number_of_arguments => 0,
            start_date => NULL,
            repeat_interval => 'FREQ=DAILY;BYDAY=MON,TUE,WED,THU,FRI,SAT,SUN;BYHOUR=4;BYMINUTE=0;BYSECOND=0',
            end_date => NULL,
            enabled => FALSE,
            auto_drop => FALSE,
            comments => '');

    DBMS_SCHEDULER.SET_ATTRIBUTE(
             name => '"JOB_EXPORTA_PULMAO_DOCA"',
             attribute => 'logging_level', value => DBMS_SCHEDULER.LOGGING_OFF);

    DBMS_SCHEDULER.enable(
             name => '"JOB_EXPORTA_PULMAO_DOCA"');
END;
