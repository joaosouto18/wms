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
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $form = new \Wms\Module\Produtividade\Form\FormProdutividadeDetalhada();
        $this->view->form = $form;
        $idUsuario = $this->_getParam('usuario');
        $idExpedicao = $this->_getParam('expedicao');
        $idMapaSeparacao = $this->_getParam('mapaSeparacao');
        $tipoQuebra = $this->_getParam('tipoQuebra');
        $dataInicio = $this->_getParam('dataInicio');
        $horaInicio = $this->_getParam('horaInicio');
        $dataFim = $this->_getParam('dataFim');
        $horaFim = $this->_getParam('horaFim');
        $params = $this->_getAllParams();
        $andWhere = ' ';
        $andWhereConf = ' ';

        if (empty($dataInicio)) {
            $hoje = new DateTime();
            $hoje->sub(new DateInterval('P01D'));
            $params['dataInicio'] = $dataInicio = $hoje->format('d/m/Y');
        }
        if (empty($dataFim)) {
            $hoje = new DateTime();
            $params['dataFim'] = $dataFim = $hoje->format('d/m/Y');
        }

        if (isset($idUsuario) && !empty($idUsuario)) {
            $andWhere     .= " AND P.COD_PESSOA = $idUsuario";
            $andWhereConf .= " AND P.COD_PESSOA = $idUsuario";
        }

        if (isset($tipoQuebra) && !empty($tipoQuebra)) {
            $andWhere     .= " AND QUEBRA.IND_TIPO_QUEBRA = 'T'";
            $andWhereConf .= " AND QUEBRA.IND_TIPO_QUEBRA = 'T'";
        }

        if (isset($idExpedicao) && !empty($idExpedicao)) {
            $andWhere     .= " AND E.COD_EXPEDICAO = $idExpedicao";
            $andWhereConf .= " AND E.COD_EXPEDICAO = $idExpedicao";
        }

        if (isset($idMapaSeparacao) && !empty($idMapaSeparacao)) {
            $andWhere     .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
            $andWhereConf .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
        }

        if (isset($dataInicio) && !empty($dataInicio)) {
            if (isset($horaInicio) && !empty($horaInicio)) {
                $andWhere     .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') >= '$dataInicio $horaInicio:00'";
                $andWhereConf .= " AND TO_CHAR(CONF.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') >= '$dataInicio $horaInicio:00'";
            } else {
                $andWhere     .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') >= '$dataInicio 00:00:00'";
                $andWhereConf .= " AND TO_CHAR(CONF.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') >= '$dataInicio 00:00:00'";
            }
        }

        if (isset($dataFim) && !empty($dataFim)) {
            if (isset($horaFim) && !empty($horaFim)) {
                $andWhere .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') <= '$dataFim $horaFim:59'";
                $andWhereConf .= " AND TO_CHAR(CONF.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') <= '$dataFim $horaFim:59'";
            } else {
                $andWhere .= " AND TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') <= '$dataFim 23:59:59'";
                $andWhereConf .= " AND TO_CHAR(CONF.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') <= '$dataFim 23:59:59'";
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
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
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
                  APONT.DTH_FIM_CONFERENCIA
            UNION
                SELECT P.NOM_PESSOA,
                    E.COD_EXPEDICAO,
                    MS.COD_MAPA_SEPARACAO,
                    SUM(CONF.QTD_EMBALAGEM * CONF.QTD_CONFERIDA * SPP.NUM_PESO) NUM_PESO,
                    TO_CHAR(MIN(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(MAX(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_FIM,
                    COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                    SUM(CONF.QTD_CONFERIDA) VOLUMES
                FROM MAPA_SEPARACAO_CONFERENCIA CONF
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                  INNER JOIN SUM_PESO_PRODUTO SPP ON SPP.COD_PRODUTO = PROD.COD_PRODUTO AND SPP.DSC_GRADE = PROD.DSC_GRADE
                  INNER JOIN ORDEM_SERVICO OS ON OS.COD_OS = CONF.COD_OS
                  INNER JOIN PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA
                WHERE 1 = 1
                  $andWhereConf
                GROUP BY P.NOM_PESSOA,
                  E.COD_EXPEDICAO,
                  MS.COD_MAPA_SEPARACAO
                ORDER BY NOM_PESSOA,
                  DTH_INICIO,
                  DTH_FIM";

        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();
        $qtdRows = count($result);
        $pesoTotal = 0;
        $volumeTotal = 0;
        $quantidadeTotal = 0;
        $seconds = 0;

        foreach ($result as $key => $value) {
            $tempoFinal = DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_FIM']);
            $tempoInicial = DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_INICIO']);
            if ($tempoFinal == null) {
                $result[$key]['TEMPO_GASTO'] = utf8_encode('Conferência em Andamento!');
                continue;
            }

            $intervalo = date_diff($tempoInicial,$tempoFinal);
            $result[$key]['TEMPO_GASTO'] = $intervalo->format('%h Hora(s) %i Minuto(s) %s Segundo(s)');
            $pesoTotal = $pesoTotal + $value['NUM_PESO'];
            $volumeTotal = $volumeTotal + $value['VOLUMES'];
            $quantidadeTotal = $quantidadeTotal + $value['QTD_PRODUTOS'];
            list($h,$i,$s) = explode(':',$intervalo->format('%h:%i:%s'));
            $seconds += $h * 3600;
            $seconds += $i * 60;
            $seconds += $s;
        }

        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        $result[$qtdRows]['NOM_PESSOA'] = 'TOTAIS';
        $result[$qtdRows]['COD_EXPEDICAO'] = '-';
        $result[$qtdRows]['COD_MAPA_SEPARACAO'] = '-';
        $result[$qtdRows]['NUM_PESO'] = $pesoTotal;
        $result[$qtdRows]['VOLUMES'] = $volumeTotal;
        $result[$qtdRows]['QTD_PRODUTOS'] = $quantidadeTotal;
        $result[$qtdRows]['DTH_INICIO'] = '-';
        $result[$qtdRows]['DTH_FIM'] = '-';
        $result[$qtdRows]['TEMPO_GASTO'] = "$hours Hora(s) $minutes Minuto(s) $seconds Segundo(s)";

        $grid = new \Wms\Module\Produtividade\Grid\ProdutividadeDetalhada();
        $this->view->grid = $grid->init($result);
        $form->populate($params);
    }
}