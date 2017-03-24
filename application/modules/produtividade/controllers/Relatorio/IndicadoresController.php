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
        $params = $this->_getAllParams();

        if (empty($params['dataInicio'])) {
            $hoje = new \DateTime();
            $hoje->sub(new \DateInterval('P01D'));
            $params['dataInicio'] = $dataInicio = $hoje->format('d/m/Y');
        }
        if (empty($params['dataFim'])) {
            $hoje = new \DateTime();
            $params['dataFim'] = $dataFim = $hoje->format('d/m/Y');
        }

        $grid = new \Wms\Module\Produtividade\Grid\ProdutividadeDetalhada();
        if(isset($params['submit'])) {
            /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepository */
            $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
            $result = $apontamentoMapaRepository->getApontamentoDetalhado($params);
            $this->view->grid = $grid->init($result);

        }
        $form->populate($params);
    }

    public function relatorioRelatorioDetalhadoAjaxAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepository */
        $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        $result = $apontamentoMapaRepository->getApontamentoDetalhado($params);

        $this->exportPDF($result,'Relatório Detalhado',utf8_encode('Relatório Detalhado'),"P");
    }
}