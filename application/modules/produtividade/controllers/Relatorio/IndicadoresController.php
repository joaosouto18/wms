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

        $orientacao = 'atividade';
        if (isset($params['orientacao'])) {
            $orientacao = $params['orientacao'];
        }

        if ($orientacao == 'atividade') {
            $SQLOrder = " ORDER BY AP.DSC_ATIVIDADE, PE.NOM_PESSOA ";
        } else {
            $SQLOrder = " ORDER BY PE.NOM_PESSOA, AP.DSC_ATIVIDADE";
        }

        $sql = "SELECT AP.DSC_ATIVIDADE,
                       PE.NOM_PESSOA,
                       CAST(SUM(AP.QTD_PRODUTOS) as NUMBER(20,2)) QTD_PRODUTOS,
                       CAST(SUM(AP.QTD_VOLUMES)  as NUMBER(20,2)) QTD_VOLUMES,
                       CAST(SUM(AP.QTD_CUBAGEM)  as NUMBER(20,2)) QTD_CUBAGEM,
                       CAST(SUM(AP.QTD_PESO)     as NUMBER(20,2)) QTD_PESO,
                       CAST(SUM(AP.QTD_PALETES)  as NUMBER(20,2)) QTD_PALETES  
                   FROM APONTAMENTO_PRODUTIVIDADE AP
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = AP.COD_PESSOA
                  WHERE TO_DATE(AP.DTH_ATIVIDADE) BETWEEN TO_DATE('$params[dataInicio]','DD/MM/YYYY') AND TO_DATE('$params[dataFim]','DD/MM/YYYY')
                  GROUP BY AP.DSC_ATIVIDADE, PE.NOM_PESSOA" . $SQLOrder;
        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();

        $grid = new \Wms\Module\Produtividade\Grid\Produtividade();
        $this->view->grid = $grid->init($result,$orientacao);

        if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
            $result = self::groupByOrientacao($result, $params['orientacao']);
            $result['dataInicio'] = $params['dataInicio'];
            $result['dataFim'] = $params['dataFim'];
            $pdfReport = new \Wms\Module\Produtividade\Report\Apontamento();
            $pdfReport->generatePDF($result);
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
}