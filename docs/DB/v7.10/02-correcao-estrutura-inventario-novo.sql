/* 
 * SCRIPT PARA: Corrigir estrutura e vinculação entre usuário-contagem-produto no inventario novo
 * DATA DE CRIAÇÃO: 31/10/2019
 * CRIADO POR: Tarcísio César
 *
 */

INSERT INTO VERSAO (DTH, NUMERO_VERSAO, SCRIPT)
VALUES (SYSDATE, '7.10', '02-correcao-estrutura-inventario-novo.sql');

ALTER TABLE INVENTARIO_CONT_END_PROD ADD (COD_INV_CONT_END_OS NUMBER(8));

BEGIN
    DELETE FROM INVENTARIO_CONT_END_PROD WHERE COD_INV_CONT_END NOT IN (
        SELECT COD_INV_CONT_END FROM INVENTARIO_CONT_END_OS
    );

    FOR I IN (SELECT ICEP.COD_INV_CONT_END_PROD X, MIN(TO_NUMBER(ICEO.COD_INV_CONT_END_OS)) Y
              FROM INVENTARIO_CONT_END_PROD ICEP
                       INNER JOIN INVENTARIO_CONT_END_OS ICEO ON ICEO.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
              GROUP BY (ICEP.COD_INV_CONT_END_PROD)
              ORDER BY TO_NUMBER(ICEP.COD_INV_CONT_END_PROD)) LOOP
        UPDATE INVENTARIO_CONT_END_PROD SET COD_INV_CONT_END_OS = I.Y WHERE COD_INV_CONT_END_PROD = I.X;
    end loop;

    DELETE FROM INVENTARIO_CONT_END_OS WHERE COD_INV_CONT_END_OS NOT IN (
        SELECT COD_INV_CONT_END_OS FROM INVENTARIO_CONT_END_PROD
    );
END;
 
ALTER TABLE INVENTARIO_CONT_END_PROD MODIFY COD_INV_CONT_END_OS NOT NULL;

ALTER TABLE INVENTARIO_CONT_END_PROD DROP CONSTRAINT FK_INVCONTENDPROD_INVCONTEND;
ALTER TABLE INVENTARIO_CONT_END_PROD ADD CONSTRAINT FK_INVCONTENDPROD_INVCONTENDOS
    foreign key (COD_INV_CONT_END_OS) references INVENTARIO_CONT_END_OS;

ALTER TABLE INVENTARIO_CONT_END_PROD DROP COLUMN COD_INV_CONT_END;