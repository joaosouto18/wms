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

        $sql = "SELECT 'ENDERECAMENTO' as OPERACAO,
                       TO_DATE(RE.DTH_ATENDIMENTO) as DTH_ATIVIDADE,
                       PE.NOM_PESSOA,
                       SUM(NVL(SPP.NUM_PESO, PV.NUM_PESO) * PP.QTD) as PESO,
                       SUM(NVL(SPP.NUM_CUBAGEM, PV.NUM_CUBAGEM) * PP.QTD) as CUBAGEM,
                       COUNT (DISTINCT P.UMA) as QTD_PALETES,
                       --SUM(PP.QTD) as QTD_VOLUMES,
                       COUNT (DISTINCT PP.COD_PRODUTO || '/' || PP.DSC_GRADE) as QTD_PRODUTOS    
                   FROM PALETE P
                  INNER JOIN PALETE_PRODUTO PP ON P.UMA = PP.UMA
                   LEFT JOIN SUM_PESO_PRODUTO SPP ON SPP.COD_PRODUTO = PP.COD_PRODUTO AND SPP.DSC_GRADE = PP.DSC_GRADE AND PP.COD_PRODUTO_VOLUME IS NULL
                   LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = PP.COD_PRODUTO_VOLUME
                  INNER JOIN RESERVA_ESTOQUE_ENDERECAMENTO REE ON REE.UMA = P.UMA
                  INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = RE.COD_USUARIO_ATENDIMENTO
                  WHERE P.COD_STATUS = 536
                  AND RE.DTH_ATENDIMENTO IS NOT NULL
                  AND RE.IND_ATENDIDA = 'S'
                  AND TO_DATE(RE.DTH_ATENDIMENTO) BETWEEN TO_DATE('$params[dataInicio]','DD/MM/YYYY') AND TO_DATE('$params[dataFim]','DD/MM/YYYY')
                  GROUP BY PE.NOM_PESSOA, TO_DATE(RE.DTH_ATENDIMENTO)";
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