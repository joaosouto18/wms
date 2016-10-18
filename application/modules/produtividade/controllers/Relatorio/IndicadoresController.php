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

        $procedureSQL = "CALL PROC_ATUALIZA_APONTAMENTO(SYSDATE,SYSDATE)";
        $procedure = $this->em->getConnection()->prepare($procedureSQL);
        $procedure->execute();
        $this->em->flush();


        $sql = "
           SELECT DSC_ATIVIDADE as OPERACAO,
                  DTH_ATIVIDADE,
                  P.NOM_PESSOA,
                  QTD_PESO AS PESO,
                  QTD_CUBAGEM AS CUBAGEM,
                  QTD_PALETES,
                  QTD_VOLUMES,
                  QTD_PRODUTOS
             FROM APONTAMENTO_PRODUTIVIDADE A
             LEFT JOIN PESSOA P ON A.COD_PESSOA = P.COD_PESSOA
             WHERE TO_DATE(A.DTH_ATIVIDADE) BETWEEN TO_DATE('$params[dataInicio]','DD/MM/YYYY') AND TO_DATE('$params[dataFim]','DD/MM/YYYY')
        ";

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
                $groupBy['rows'][$row['OPERACAO']][] = $row;
            }
        } else {
            $groupBy['orientacao'] = 'Funcionario';
            foreach ($result as $row) {
                array_push($groupBy['rows'][$row['FUNCIONARIO']][],$row);
            }
        }
        return $groupBy;
    }
}