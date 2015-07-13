<?php
namespace Wms\Module\Expedicao\Report;
use Wms\Module\Web\Report;

class SaidaProduto extends Report
{

    public function init(array $params = array())
    {
        if (empty($params['idProduto'])) {
            return false;
        }
        $em = $this->getEm();
        $produtos = $em->getRepository('wms:Expedicao')->getRelatorioSaidaProdutos($params['idProduto'], $params['grade']);
        if ($produtos == null) {
            return false;
        }
        $grade = null;
        if ($params['grade']) {
            $grade = ' Grade:'.$params['grade'];
        }

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');
        $pdf->setTitle(utf8_decode('Saída Cod.Produto:'.$produtos[0]['codProduto'].$grade))
                ->setLabelHeight(6)
                ->setColHeight(7);

        // header
        $pdf->addLabel(0, 35, 'Data Bipe', 0, 0, 'L');
        $pdf->addLabel(0, 90, 'Itinerario', 0, 0, 'L');
        $pdf->addLabel(0, 20, 'Cod.Carga', 0, 0, 'L');
        $pdf->addLabel(0, 10, 'Exp.', 0, 0, 'L');
        $pdf->addLabel(0, 57, 'DTH.Inicio/Fim', 0, 0, 'C');
        $pdf->addLabel(0, 22, 'Cod.Pedido', 0, 0, 'C');
        $pdf->addLabel(0, 45, 'Cod. Cliente', 0, 1, 'C');


        foreach ($produtos as $produto) {

            $dataBipe = null;
            if ($produto['dataConferencia']) {
                $dataBipe = $produto['dataConferencia']->format('d/m/Y H:i:s');
            }
            $dataInicio = null;
            if ($produto['dataInicio']) {
                $dataInicio = $produto['dataInicio']->format('d/m/Y');
            }
            $dataFim = null;
            if ($produto['dataFinalizacao']) {
                $dataFim = $produto['dataFinalizacao']->format('d/m/Y');
            }

            $pdf->addCol(0, 35, $dataBipe, 0, 0, 'L');
            $pdf->addCol(0, 90, $produto['itinerario'].'('.$produto['idItinerario'].')', 0, 0, 'L');
            $pdf->addCol(0, 20, $produto['codCargaExterno'], 0, 0, 'L');
            $pdf->addCol(0, 10, $produto['idExpedicao'], 0, 0, 'L');
            $pdf->addCol(0, 57, $dataInicio.'-'.$dataFim, 0, 0, 'C');
            $pdf->addCol(0, 22, $produto['idPedido'], 0, 0, 'C');
            $pdf->addCol(0, 45, $produto['codClienteExterno'], 0, 1, 'C');
        }

        // page
        $pdf->AddPage()
                ->render()
                ->Output('saida-produto'.$produtos[0]['codProduto'].'.pdf', 'D');
    }

}
