<?php

use \Wms\Module\Expedicao\Form as ExpedicaoForm;

class Expedicao_Relatorio_SaidaController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form = new ExpedicaoForm\SaidaProduto();
        $form->init($utilizaGrade);
        
        $params = $form->getParams();

        if ($params) {
            ini_set('memory_limit', '-1');
            $form->populate($params);
            $Report = new \Wms\Module\Expedicao\Report\SaidaProduto();

            $report = "S";
            if (!(isset($params['dataInicial']) && (!empty($params['dataInicial'])))) {
                $this->addFlashMessage('info', 'É necessário que se especifique um intervalo de datas para o relatório');
                $report = "N";
            }

            if ($report == "S") {
                if (!(isset($params['dataFinal']) && (!empty($params['dataFinal'])))) {
                    $this->addFlashMessage('info', 'É necessário que se especifique um intervalo de datas para o relatório');
                    $report = "N";
                }
            }

            if ($report == "S") {
                if ($Report->init($params) == false) {
                    $this->addFlashMessage('error', 'Nenhuma informação encontrada');
                }
            }
        }

        $this->view->form = $form;
    }

    public function rastreioAction()
    {
        $form = new ExpedicaoForm\RelatorioRastreio();
        $this->view->form = $form;
    }

    public function getRastreioResultsAjaxAction()
    {
        try {
            $params = $this->getRequest()->getParams();
            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);
            ini_set('memory_limit', '-1');
            $result = $this->_em->getRepository(\Wms\Domain\Entity\Expedicao::class)->getRastreioExpedicoes($params);
            $this->_helper->json(['status' => 'Ok', 'results' => $result]);
        } catch (Exception $e) {
            $this->_helper->json(['status' => 'error', 'exception' => $e->getMessage()]);
        }
    }

    public function exportRastreioAjaxAction()
    {
        $params = json_decode($this->getRequest()->getRawBody(),true);
        $headerMap = [
            "codExpedicao" => 'Expedição',
            "codCarga" => 'Carga',
            "codPedido" => 'Pedido',
            "dthInicio" => 'Início',
            "dthFim" => 'Finalização',
            "nomCliente" => 'Cliente',
            "codProduto" => 'Item',
            "dscProd" => 'Produto',
            "grade" => 'Grade',
            "lote" => 'Lote',
            "qtdAtendida" => 'Qtde.'
        ];
        if ($params['destino'] == 'pdf')
            $this->exportPDF($params['results'], 'Relatório de Rastreio de Expedição', 'Rastreio de Expedição', 'L', $headerMap);
        if ($params['destino'] == 'csv')
            $this->exportCSV($params['results'], 'Relatório de Rastreio de Expedição', true, $headerMap);
    }
}