/* 
 * SCRIPT PARA: Scripts gerais do Cross-Docking
 * DATA DE CRIAÇÃO: 21/01/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
  CHECK_RESULT VARCHAR2(100);
BEGIN
  SELECT FUNC_CHECK_SCRIPT('xx-cross-docking.sql', '') INTO CHECK_RESULT FROM DUAL;
  IF (CHECK_RESULT <> 'TRUE') THEN
    DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
  ELSE
    INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT) VALUES (SYSDATE, '7', 'xx-cross-docking.sql');
    /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

      EXECUTE IMMEDIATE 'ALTER TABLE TIPO_PEDIDO_EXPEDICAO ADD (COD_EXTERNO VARCHAR2(30))';
      INSERT INTO TIPO_PEDIDO_EXPEDICAO (COD_TIPO_PEDIDO_EXPEDICAO, DSC_TIPO_PEDIDO_EXPEDICAO, COD_EXTERNO)
        VALUES (1, 'Mostruário','MOSTRUARIO'),
                   (2, 'Reposição','REPOSICAO'),
                   (3, 'Entrega','ENTREGA'),
                   (4, 'Sugestão','SUGESTAO'),
                   (5, 'Avulso','AVULSO'),
                   (6, 'Assistencia','ASSISTENCIA'),
                   (7, 'Kit','KIT'),
                   (8, 'Venda Balcão','VENDA_BALCAO'),
                   (9, 'Simples Remessa','SIMPLES_REMESSA'),
                   (10, 'Reentrega','REENTREGA'),
                   (11, 'Pedido Antecipado','PEDIDO_ANTECIPADO'),
                   (12, 'Outros','OUTROS'),
                   (13, 'CROSS DOCKING','CROSS_DOCKING'),
               )
      ;

    /************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
    DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
  END IF;
END;
 
 