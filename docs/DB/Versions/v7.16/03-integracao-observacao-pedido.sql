INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, 'v7.16', '03-integracao-observacao-pedido.sql');

alter table pedido
add (dsc_observacao long null);

alter table integracao_pedido
add (dsc_observacao_integracao long null);


------------------------------------------------------------------------------------------------------------------------


alter table pedido
add (ind_faturado char(1) default 'N' not null);

CREATE OR REPLACE TRIGGER PEDIDO_FATURADO
  BEFORE INSERT ON PEDIDO
  FOR EACH ROW
BEGIN
  SELECT 'N'
  INTO :NEW.IND_FATURADO
  FROM DUAL;
END;

alter table nota_fiscal_saida
add (dth_faturamento_erp timestamp not null,
     dth_integracao_nf_saida timestamp default sysdate not null);

CREATE OR REPLACE TRIGGER DTH_INTEGR_NF_SAIDA
  BEFORE INSERT ON NOTA_FISCAL_SAIDA
  FOR EACH ROW
BEGIN
  SELECT SYSDATE
  INTO :NEW.DTH_INTEGRACAO_NF_SAIDA
  FROM DUAL;
END;