<?php

use Wms\Module\Web\Controller\Action;

class Produtividade_Relatorio_IndicadoresController extends Action {

    public function indexAction() {
        ini_set('memory_limit', '-1');
        $params = $this->_getAllParams();
        $form = new \Wms\Module\Produtividade\Form\FormProdutividade();

        if (!isset($params['dataInicio']) || empty($params['dataInicio'])) {
            $dataI1 = new DateTime();
            $params['dataInicio'] = '01/' . $dataI1->format('m/Y');
        }
        if (!isset($params['dataFim']) || empty($params['dataFim'])) {
            $dataF2 = new DateTime();
            $params['dataFim'] = $dataF2->format('d/m/Y');
        }

        $form->populate($params);
        $this->view->form = $form;

        $hoje = date('d/m/Y');
        $procedureSQL = "CALL PROC_PRODUTIVIDADE_DETALHE('$hoje','$hoje')";

        $procedure = $this->em->getConnection()->prepare($procedureSQL);
        $procedure->execute();
        $this->em->flush();

        $orientacao = 'atividade';
        $tipo = 'resumido';

        if (isset($params['orientacao'])) {
            $orientacao = $params['orientacao'];
        }
        if (isset($params['tipo'])) {
            $tipo = $params['tipo'];
        }

        $sqlOrderTipo = "";
        if ($tipo == 'detalhado') {
            $sqlOrderTipo = " TO_CHAR(P.DTH_INICIO,'DD/MM/YYYY'), ";
        }
        if ($orientacao == 'atividade') {
            $SQLOrder = " ORDER BY $sqlOrderTipo P.DSC_ATIVIDADE, PE.NOM_PESSOA ";
        } else {
            $SQLOrder = " ORDER BY $sqlOrderTipo PE.NOM_PESSOA, P.DSC_ATIVIDADE";
        }

        $SQLWHere = "";
        if (isset($params['atividade']) && !empty($params['atividade'])) {
            $SQLWHere = " AND DSC_ATIVIDADE LIKE '%" . $params['atividade'] . "%'";
        }

        $sql = "SELECT ";
        if ($tipo == 'detalhado') {
            $sql .= " TO_CHAR(P.DTH_INICIO,'DD/MM/YYYY') as DIA,";
        }
        $sql .= " P.DSC_ATIVIDADE,
                       PE.NOM_PESSOA,
                       COUNT(P.QTD_PRODUTOS)as QTD_PRODUTOS,
                       SUM(P.QTD_VOLUMES) as QTD_VOLUMES,
                       SUM(P.QTD_CUBAGEM) as QTD_CUBAGEM,
                       SUM(P.QTD_PESO)    as QTD_PESO,
                       SUM(P.QTD_PALETES) as QTD_PALETES,
                       SUM(P.QTD_CARGA)   as QTD_CARGA
                   FROM PRODUTIVIDADE_DETALHE P
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = P.COD_PESSOA
                  WHERE TO_DATE(P.DTH_INICIO) BETWEEN TO_DATE('$params[dataInicio] 00:00:00','DD/MM/YYYY HH24:MI:SS') AND TO_DATE('$params[dataFim] 23:59:59','DD/MM/YYYY HH24:MI:SS')
                  $SQLWHere
                  GROUP BY ";
        if ($tipo == 'detalhado') {
            $sql .= " TO_CHAR(P.DTH_INICIO,'DD/MM/YYYY'), ";
        }
        $sql .= "P.DSC_ATIVIDADE, PE.NOM_PESSOA" . $SQLOrder;
        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();
        $grid = new \Wms\Module\Produtividade\Grid\Produtividade();
        $this->view->grid = $grid->init($result, $orientacao, $tipo);

        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            if (!empty($result)) {
                $result = self::groupByOrientacao($result, $params['orientacao']);
                $result['dataInicio'] = $params['dataInicio'];
                $result['dataFim'] = $params['dataFim'];
                $pdfReport = new \Wms\Module\Produtividade\Report\Apontamento();
                $pdfReport->generatePDF($result);
            } else {
                $this->addFlashMessage('error', "Nenhum resultado encontrado entre $params[dataInicio] e $params[dataFim]");
            }
        }
    }

    private function groupByOrientacao($result, $orientacao) {
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

    public function relatorioDetalhadoAction() {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $form = new \Wms\Module\Produtividade\Form\FormProdutividadeDetalhada();
        $this->view->form = $form;
        $params = $this->_getAllParams();

        $hoje = date('d/m/Y');
        $procedureSQL = "CALL (PROC_PRODUTIVIDADE_DETALHE('$hoje','$hoje'))";
        $procedure = $this->em->getConnection()->prepare($procedureSQL);
        $procedure->execute();
        $this->em->flush();

        if (empty($params['dataInicio'])) {
            $hoje = new \DateTime();
            $hoje->sub(new \DateInterval('P01D'));
            $dataInicio = $hoje->format('d/m/Y');
            $params['dataInicio'] = $dataInicio;
        }
        if (empty($params['dataFim'])) {
            $hoje = new \DateTime();
            $dataFim = $hoje->format('d/m/Y');
            $params['dataFim'] = $dataFim;
        }
        if (empty($params['horaFim'])) {
            $params['horaFim'] = "";
        }
        if (empty($params['horaInicio'])) {
            $params['horaInicio'] = "";
        }
        if (empty($params['tipoQuebra'])) {
            $params['tipoQuebra'] = "";
        }
        if (empty($params['identidade'])) {
            $params['identidade'] = "";
        }
        if (empty($params['atividade'])) {
            $params['atividade'] = "";
        }
        if (empty($params['usuario'])) {
            $params['usuario'] = "";
        }
        if (empty($params['ordem'])) {
            $params['ordem'] = "";
        }
        $grid = new \Wms\Module\Produtividade\Grid\ProdutividadeDetalhada();
        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepository */
        $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        $result = $apontamentoMapaRepository->getProdutividadeDetalhe($params);
        $this->view->grid = $grid->init($result)->render();

        $form->populate($params);
    }

    public function relatorioRelatorioDetalhadoAjaxAction() {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepository */
        $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        $result = $apontamentoMapaRepository->getProdutividadeDetalhe($params);

        $relatorio = new \Wms\Module\Produtividade\Printer\ProdutividadeDetalhada('L', 'mm', 'A4');
        $relatorio->imprimir($result);
    }

}
