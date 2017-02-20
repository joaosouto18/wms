<?php
use Wms\Module\Web\Controller\Action;

class Produtividade_Relatorio_IndicadoresController  extends Action
{
    public function indexAction()
    {
        ini_set('memory_limit', '-1');
        $params = $this->_getAllParams();
        $form = new \Wms\Module\Produtividade\Form\FormProdutividade();

        if ( !isset($params['dataInicio']) ||empty($params['dataInicio'])) {
            $dataI1 = new DateTime();
            $params['dataInicio'] = '01/'.$dataI1->format('m/Y');
        }
        if ( !isset($params['dataFim']) ||empty($params['dataFim'])) {
            $dataF2 = new DateTime();
            $params['dataFim'] = $dataF2->format('d/m/Y');
        }

        $form->populate($params);
        $this->view->form = $form;

        $hoje = date('d/m/Y');
        $procedureSQL = "CALL PROC_ATUALIZA_APONTAMENTO('$hoje','$hoje')";
        $procedure = $this->em->getConnection()->prepare($procedureSQL);
        $procedure->execute();
        $this->em->flush();

        $orientacao = 'atividade';
        if (isset($params['orientacao'])) {
            $orientacao = $params['orientacao'];
        }

        if ($orientacao == 'atividade') {
            $SQLOrder = " ORDER BY AP.DSC_ATIVIDADE, PE.NOM_PESSOA ";
        } else {
            $SQLOrder = " ORDER BY PE.NOM_PESSOA, AP.DSC_ATIVIDADE";
        }

        $SQLWHere = "";
        if (isset($params['atividade']) && !empty($params['atividade'])) {
            $SQLWHere = " AND DSC_ATIVIDADE = '".$params['atividade']."'";
        }


        $sql = "SELECT AP.DSC_ATIVIDADE,
                       PE.NOM_PESSOA,
                       SUM(AP.QTD_PRODUTOS)as QTD_PRODUTOS,
                       SUM(AP.QTD_VOLUMES) as QTD_VOLUMES,
                       SUM(AP.QTD_CUBAGEM) as QTD_CUBAGEM,
                       SUM(AP.QTD_PESO)    as QTD_PESO,
                       SUM(AP.QTD_PALETES) as QTD_PALETES,
                       SUM(AP.QTD_CARGA)   as QTD_CARGA
                   FROM APONTAMENTO_PRODUTIVIDADE AP
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = AP.COD_PESSOA
                  WHERE TO_DATE(AP.DTH_ATIVIDADE) BETWEEN TO_DATE('$params[dataInicio]','DD/MM/YYYY') AND TO_DATE('$params[dataFim]','DD/MM/YYYY')
                  $SQLWHere
                  GROUP BY AP.DSC_ATIVIDADE, PE.NOM_PESSOA" . $SQLOrder;
        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();

        $grid = new \Wms\Module\Produtividade\Grid\Produtividade();
        $this->view->grid = $grid->init($result,$orientacao);

        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            if (!empty($result)) {
                $result = self::groupByOrientacao($result, $params['orientacao']);
                $result['dataInicio'] = $params['dataInicio'];
                $result['dataFim'] = $params['dataFim'];
                $pdfReport = new \Wms\Module\Produtividade\Report\Apontamento();
                $pdfReport->generatePDF($result);
            }else {
                $this->addFlashMessage('error',"Nenhum resultado encontrado entre $params[dataInicio] e $params[dataFim]");
            }
        }
    }

    private function groupByOrientacao ($result, $orientacao)
    {
        $groupBy = array();
        if ($orientacao == 'atividade') {
            $groupBy['orientacao'] = 'Atividade';
            foreach ($result as $row) {
                $groupBy['rows'][$row['DSC_ATIVIDADE']][] = $row;
            }
        } else {
            $groupBy['orientacao'] = 'Funcionario';
            foreach ($result as $row) {
                $groupBy['rows'][$row['NOM_PESSOA']][] = $row;
            }
        }
        return $groupBy;
    }

    public function relatorioDetalhadoAction()
    {
        $form = new \Wms\Module\Produtividade\Form\FormProdutividadeDetalhada();
        $this->view->form = $form;
        $idUsuario = $this->_getParam('usuario');
        $idExpedicao = $this->_getParam('expedicao');
        $idMapaSeparacao = $this->_getParam('mapaSeparacao');
        $dataInicio = $this->_getParam('dataInicio');
        $horaInicio = $this->_getParam('horaInicio');
        $dataFim = $this->_getParam('dataFim');
        $horaFim = $this->_getParam('horaFim');
        $andWhere = ' ';
        if (isset($idUsuario) && !empty($idUsuario))
            $andWhere .= " AND P.COD_PESSOA = $idUsuario";

        if (isset($idExpedicao) && !empty($idExpedicao))
            $andWhere .= " AND E.COD_EXPEDICAO = $idExpedicao";

        if (isset($idMapaSeparacao) && !empty($idMapaSeparacao))
            $andWhere .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";

        if (isset($dataInicio) && !empty($dataInicio)) {
            if (isset($horaInicio) && !empty($horaInicio)) {
                $andWhere .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') > '$dataInicio $horaInicio:00'";
            } else {
                $andWhere .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') > '$dataInicio 00:00:00'";
            }
        }

        if (isset($dataFim) && !empty($dataFim)) {
            if (isset($horaFim) && !empty($horaFim)) {
                $andWhere .= " AND TO_CHAR(APONT.DTH_FIM_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') < '$dataFim $horaFim:00'";
            } else {
                $andWhere .= " AND TO_CHAR(APONT.DTH_FIM_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') < '$dataFim 23:59:59'";
            }
        }

        $sql = "SELECT P.NOM_PESSOA,
                        E.COD_EXPEDICAO,
                        MS.COD_MAPA_SEPARACAO,
                        SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA * SPP.NUM_PESO) NUM_PESO,
                        TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                        TO_CHAR(APONT.DTH_FIM_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_FIM,
                        COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                        SUM(MSC.QTD_CONFERIDA) VOLUMES
                FROM APONTAMENTO_SEPARACAO_MAPA APONT
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = APONT.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PESSOA P ON P.COD_PESSOA = APONT.COD_USUARIO
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = MSC.COD_PRODUTO AND PROD.DSC_GRADE = MSC.DSC_GRADE
                  INNER JOIN SUM_PESO_PRODUTO SPP ON SPP.COD_PRODUTO = PROD.COD_PRODUTO AND SPP.DSC_GRADE = PROD.DSC_GRADE
                WHERE 1 = 1
                  $andWhere
                GROUP BY P.NOM_PESSOA,
                  E.COD_EXPEDICAO,
                  MS.COD_MAPA_SEPARACAO,
                  APONT.DTH_CONFERENCIA,
                  APONT.DTH_FIM_CONFERENCIA";

        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();

        foreach ($result as $key => $value) {
            $tempoFinal = DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_FIM']);
            $tempoInicial = DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_INICIO']);
            if ($tempoFinal == null) {
                $result[$key]['TEMPO_GASTO'] = utf8_encode('Conferência em Andamento!');
                continue;
            }

            $intervalo = date_diff($tempoInicial,$tempoFinal);
            $result[$key]['TEMPO_GASTO'] = $intervalo->format('%h Hora(s) %i Minuto(s) %s Segundo(s)');
        }

        $grid = new \Wms\Module\Produtividade\Grid\ProdutividadeDetalhada();
        $this->view->grid = $grid->init($result);
    }
}