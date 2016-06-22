ALTER TABLE MAPA_SEPARACAO_QUEBRA DROP CONSTRAINT MAPA_SEPARACAO_QUEBRA_MP_FK;
ALTER TABLE MAPA_SEPARACAO_QUEBRA ADD CONSTRAINT MAPA_SEPARACAO_QUEBRA_MP_FK FOREIGN KEY (COD_MAPA_SEPARACAO) REFERENCES MAPA_SEPARACAO (COD_MAPA_SEPARACAO) ON DELETE CASCADE ENABLE;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA DROP CONSTRAINT MAPA_SEPARACAO_CONF_MP_FK;
ALTER TABLE MAPA_SEPARACAO_CONFERENCIA ADD CONSTRAINT MAPA_SEPARACAO_CONF_MP_FK FOREIGN KEY (COD_MAPA_SEPARACAO) REFERENCES MAPA_SEPARACAO (COD_MAPA_SEPARACAO) ON DELETE CASCADE ENABLE;
ALTER TABLE MAPA_SEPARACAO_PRODUTO DROP CONSTRAINT MAPA_SEPARACAO_PRODUTO_MP_FK;
ALTER TABLE MAPA_SEPARACAO_PRODUTO ADD CONSTRAINT MAPA_SEPARACAO_PRODUTO_MP_FK FOREIGN KEY (COD_MAPA_SEPARACAO) REFERENCES MAPA_SEPARACAO (COD_MAPA_SEPARACAO) ON DELETE CASCADE ENABLE;
