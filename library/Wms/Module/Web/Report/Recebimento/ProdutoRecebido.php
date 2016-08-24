<?php

namespace Wms\Module\Web\Report\Recebimento;

use Wms\Module\Web\Report;

/**
 * Description of ConferenciaCega
 *
 * @author medina
 */
class ProdutoRecebido extends Report
{

    public function init(array $params = array())
    {
        $em = $this->getEm();
        $produtos = $em->getRepository('wms:NotaFiscal')->getProdutoRecebido($params);

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');
        $pdf->setTitle(utf8_decode('Relatório de Produtos Recebidos'))
            ->setLabelHeight(6)
            ->setColHeight(7);

        // header
        $pdf->addLabel(0, 16, 'Receb.', 0, 0, 'L');
        $pdf->addLabel(0, 26, 'Data Receb.', 0, 0, 'C');
        $pdf->addLabel(0, 24, 'Nota Fiscal', 0, 0, 'L');
        $pdf->addLabel(0, 15, 'Serie', 0, 0, 'C');
        $pdf->addLabel(0, 28, 'Cod. Produto', 0, 0, 'L');
        $pdf->addLabel(0, 15, 'Grade', 0, 0, 'L');
        $pdf->addLabel(0, 92, utf8_decode('Descrição'), 0, 0, 'L');
        $pdf->addLabel(0, 13, 'Emb.', 0, 0, 'C');
        $pdf->addLabel(0, 15, 'Qtd.:', 0, 0, 'C');
        $pdf->addLabel(0, 18, 'Conferida', 0, 0, 'C');
        $pdf->addLabel(0, 18, utf8_decode('Diverg.'), 0, 1, 'C');


        foreach ($produtos as $produto) {

            $dataRecebimento = \DateTime::createFromFormat('Y-m-d H:i:s', $produto['DTH_FINAL_RECEB']);

            $pdf->addCol(0, 16, $produto['COD_RECEBIMENTO'], 0, 0, 'L');
            $pdf->addCol(0, 26, $dataRecebimento->format('d/m/Y'), 0, 0, 'C');
            $pdf->addCol(0, 24, $produto['NUM_NOTA_FISCAL'], 0, 0, 'L');
            $pdf->addCol(0, 15, $produto['COD_SERIE_NOTA_FISCAL'], 0, 0, 'C');
            $pdf->addCol(0, 28, $produto['COD_PRODUTO'], 0, 0, 'L');
            $pdf->addCol(0, 15, $produto['DSC_GRADE'], 0, 0, 'L');
            $pdf->addCol(0, 92, substr(/*$produto['DSC_PRODUTO']*/'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',0,42), 0, 0, 'L');
            $pdf->addCol(0, 13, $produto['DSC_EMBALAGEM'], 0, 0, 'C');
            $pdf->addCol(0, 15, $produto['QTD_ITEM'], 0, 0, 'C');
            $pdf->addCol(0, 18, $produto['QTD_CONFERIDA'], 0, 0, 'C');
            $pdf->addCol(0, 18, $produto['QTD_DIVERGENCIA'], 0, 1, 'C');
        }

        // page
        $pdf->AddPage()
            ->render()
            ->Output('relatorio.pdf', 'D');
    }

}
