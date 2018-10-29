<?php

use Wms\Module\Web\Controller\Action;

class Produtividade_ProdutividadeController extends Action {

    /**
     * Função que roda a produtividade do dia anterior
     */
    public function runAction() {

        ini_set('memory_limit', '-1');
        $dia = $this->_getParam('dia');
        $ontem = date("d/m/Y", strtotime("-1 day"));
        $diaInicio = date("d/m/Y", strtotime("-$dia day"));
        echo "CALL PROC_PRODUTIVIDADE_DETALHE('$diaInicio','$ontem')";
        $procedureSQL = "CALL PROC_PRODUTIVIDADE_DETALHE('$diaInicio','$ontem')";
        $procedure = $this->conn->prepare($procedureSQL);
        $procedure->execute();

        $this->em->flush();

        \Zend_Layout::getMvcInstance()->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

}
