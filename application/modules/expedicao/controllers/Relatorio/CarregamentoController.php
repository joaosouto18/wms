<?php
use Wms\Module\Expedicao\Report\RelatorioCarregamento;

class Expedicao_Relatorio_CarregamentoController extends \Wms\Controller\Action
{
    public function imprimirAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('id');
        $RelCarregamento    = new RelatorioCarregamento("L","mm","A4");
        $modelo = $this->getSystemParameterValue("MODELO_RELATORIOS");

        $RelCarregamento->imprimir($idExpedicao,$modelo);
    }
}