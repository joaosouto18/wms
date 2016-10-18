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


        $sql = "SELECT AP.DSC_ATIVIDADE,
                       PE.NOM_PESSOA,
                       SUM(AP.QTD_PRODUTOS) QTD_PRODUTOS,
                       SUM(AP.QTD_VOLUMES) QTD_VOLUMES,
                       SUM(AP.QTD_CUBAGEM) QTD_CUBAGEM,
                       SUM(AP.QTD_PESO) QTD_PESO,
                       SUM(AP.QTD_PALETES) QTD_PALETES  
                   FROM APONTAMENTO_PRODUTIVIDADE AP
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = AP.COD_PESSOA
                  WHERE TO_DATE(AP.DTH_ATIVIDADE) BETWEEN TO_DATE('$params[dataInicio]','DD/MM/YYYY') AND TO_DATE('$params[dataFim]','DD/MM/YYYY')
                  GROUP BY AP.DSC_ATIVIDADE, PE.NOM_PESSOA";
        $result = $this->em->getConnection()->executeQuery($sql)->fetchAll();

        $grid = new \Wms\Module\Produtividade\Grid\Produtividade();
        $this->view->grid = $grid->init($result);

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