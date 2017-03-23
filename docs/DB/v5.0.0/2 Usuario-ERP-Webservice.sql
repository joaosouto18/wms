INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '5.0.0','2 Usuario-ERP-Webservice.sql');

INSERT INTO PESSOA_FISICA ( COD_PESSOA, NUM_CPF )
VALUES ( 1,  '00000000000' );

INSERT INTO USUARIO
( COD_USUARIO, DSC_IDENTIFICACAO_USUARIO, DSC_SENHA_ACESSO, IND_ATIVO, IND_SENHA_PROVISORIA)
VALUES ( 1, 'ERP', 'ERP', 'S', 'N' );

INSERT INTO USUARIO_PERFIL_USUARIO
( COD_USUARIO, COD_PERFIL_USUARIO )
VALUES ( 1, 81 );

INSERT INTO PARAMETRO (
  COD_PARAMETRO, COD_CONTEXTO_PARAMETRO, DSC_PARAMETRO,
  DSC_TITULO_PARAMETRO, IND_PARAMETRO_SISTEMA, COD_TIPO_ATRIBUTO, DSC_VALOR_PARAMETRO)
VALUES (
  SQ_PARAMETRO_01.NEXTVAL,
  (SELECT COD_CONTEXTO_PARAMETRO FROM CONTEXTO_PARAMETRO WHERE DSC_CONTEXTO_PARAMETRO LIKE 'PARAMETROS DE INTEGRAÇÃO'),
  'ID_USER_ERP',
  'Código de usuário do ERP',
  'S', 'A', '1');