/* 
 * SCRIPT PARA: Corrigir vinculação entre usuário-contagem-produto no inventario novo
 * DATA DE CRIAÇÃO: 31/10/2019 
 * CRIADO POR: Tarcísio César
 *
 */
DECLARE
    CHECK_RESULT VARCHAR2(100);
BEGIN
    SELECT FUNC_CHECK_SCRIPT('x-correcao-estrutura-inventario-novo.sql', '') INTO CHECK_RESULT FROM DUAL;
    IF (CHECK_RESULT <> 'TRUE') THEN
        DBMS_OUTPUT.PUT_LINE(CHECK_RESULT);
    ELSE
        INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
        VALUES (SYSDATE, '7.11', 'x-correcao-estrutura-inventario-novo.sql');
        /************************************************************************
**        COLOQUE O SCRIPT À SER EXECUTADO ENTRE ESTA DEMARCAÇÃO       **
************************************************************************/

        EXECUTE IMMEDIATE 'ALTER TABLE INVENTARIO_CONT_END_PROD ADD (COD_INV_CONT_END_OS NUMBER(8))';

        BEGIN
            FOR I IN (SELECT ICEP.COD_INV_CONT_END_PROD X, MIN(TO_NUMBER(ICEO.COD_INV_CONT_END_OS)) Y
                      FROM INVENTARIO_CONT_END_PROD ICEP
                               INNER JOIN INVENTARIO_CONT_END_OS ICEO ON ICEO.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
                      GROUP BY (ICEP.COD_INV_CONT_END_PROD)
                      ORDER BY TO_NUMBER(ICEP.COD_INV_CONT_END_PROD)) LOOP
                UPDATE INVENTARIO_CONT_END_PROD SET COD_INV_CONT_END_OS = I.Y WHERE COD_INV_CONT_END_PROD = I.X;
            end loop;
        END;

        DELETE FROM INVENTARIO_CONT_END_OS WHERE COD_INV_CONT_END_OS NOT IN (
            SELECT COD_INV_CONT_END_OS FROM INVENTARIO_CONT_END_PROD
        );


        EXECUTE IMMEDIATE 'ALTER TABLE INVENTARIO_CONT_END_PROD DROP CONSTRAINT FK_INVCONTENDPROD_INVCONTEND';
        EXECUTE IMMEDIATE 'ALTER TABLE INVENTARIO_CONT_END_PROD ADD CONSTRAINT FK_INVCONTENDPROD_INVCONTENDOS
                                         foreign key (COD_INV_CONT_END_OS) references INVENTARIO_CONT_END_OS';

        EXECUTE IMMEDIATE 'ALTER TABLE INVENTARIO_CONT_END_PROD DROP COLUMN COD_INV_CONT_END';

/************************************************************************
**                 NÃO ALTERAR ABAIXO DESTA REGIÃO                     **
************************************************************************/
        DBMS_OUTPUT.PUT_LINE('Script executado com sucesso');
    END IF;
END;
 
 