<?php

namespace Wms\Module\Web\Report\Recebimento;

use Core\Pdf;
use Wms\Module\Web\Report;

/**
 * Description of ConferenciaCega
 *
 * @author medina
 */
class ProdutoRecebido extends Pdf
{

    public function init(array $params = array())
    {
//        $em = $this->getEm();
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $produtos = $em->getRepository('wms:NotaFiscal')->getProdutoRecebido($params);

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetFont('Arial','B',10);
        $this->SetY(-10);
        $this->Cell(5, 5, utf8_decode("Relatório de Produtos Recebidos"), 0, 1);

        $codRecebimentoAnterior = null;
        $notaFiscalAnterior = null;
        $serieAnterior = null;
        $codProdutoAnterior = null;
        $dscGrade = null;
        $numeroProdutos = count($produtos);
        $y1 = 25;
        $y2 = 35;
        foreach ($produtos as $key => $produto) {
            if ($codRecebimentoAnterior != $produto['COD_RECEBIMENTO'] || $notaFiscalAnterior != $produto['NUM_NOTA_FISCAL'] || $serieAnterior != $produto['COD_SERIE_NOTA_FISCAL']) {
                $this->SetFont('Arial', 'B', 9);
                $this->Line(5,$y1, 200, $y1);
                $this->Line(5,$y2, 200, $y2);
                $this->Ln();
                $this->Ln();
                $this->Cell(25, 5, 'Cod. Receb.' ,0, 0, "L");
                $this->Cell(25, 5, 'Data Receb.', 0, 0, 'L');
                $this->Cell(-70, 5, 'Nota Fiscal', 0, 0, 'L');
                $this->Cell(0, 5, 'Conferente', 0, 0, 'C');
                $this->Cell(-105, 5, 'Fornecedor', 0, 1, 'C');

                $this->SetFont('Arial', '', 8);
                $dataRecebimento = \DateTime::createFromFormat('Y-m-d H:i:s', $produto['DTH_FINAL_RECEB']);
                $this->Cell(25, 5, $produto['COD_RECEBIMENTO'], 0, 0, 'L');
                $this->Cell(25, 5, $dataRecebimento->format('d/m/Y'), 0, 0, 'L');
                $this->Cell(-50, 5, $produto['NUM_NOTA_FISCAL'].' / '.$produto['COD_SERIE_NOTA_FISCAL'], 0, 0, 'L');
                $this->Cell(0, 5, substr($produto['NOM_PESSOA'],0,40), 0, 0, 'C');
                $this->Cell(-108, 5, substr($produto['FORNECEDOR'],0,40), 0, 1, 'C');

                $this->SetFont('Arial', 'B', 8);
//                $this->Line(5,35, 200,35);
                $this->Cell(25, 5, 'Cod. Produto', 0, 0, 'L');
                $this->Cell(77, 5, utf8_decode('Descrição'), 0, 0, 'L');
                $this->Cell(5, 5, 'Qtd.:', 0, 0, 'L');
                $this->Cell(20, 5, 'Nota', 0, 0, 'C');
                $this->Cell(15, 5, 'Conferida', 0, 0, 'C');
                $this->Cell(25, 5, utf8_decode('Divergencia'), 0, 1, 'C');

                $countProdutos = 0;
                $totalItem = 0;
                $totalConferido = 0;
                $totalDivergencia = 0;
            }

            if ($codProdutoAnterior != $produto['COD_PRODUTO'] || $dscGrade != $produto['DSC_GRADE']) {
                $this->SetFont('Arial', '', 7);
                $this->Cell(25, 5, $produto['COD_PRODUTO'].' - '.$produto['DSC_GRADE'], 0, 0, 'L');
                $this->Cell(69, 5, substr($produto['DSC_PRODUTO'],0,40), 0, 0, 'L');
                $this->Cell(45, 5, $produto['QTD_ITEM'], 0, 0, 'C');
                $this->Cell(-10, 5, $produto['QTD_CONFERIDA'], 0, 0, 'C');
                $this->Cell(50, 5, $produto['QTD_DIVERGENCIA'], 0, 1, 'C');
                $countProdutos++;
                $totalItem = $totalItem + $produto['QTD_ITEM'];
                $totalConferido = $totalConferido + $produto['QTD_CONFERIDA'];
                $totalDivergencia = $totalDivergencia + $produto['QTD_DIVERGENCIA'];
            }

            if ($key + 1 < $numeroProdutos) {
                if ($produtos[$key + 1]['COD_RECEBIMENTO'] != $produto['COD_RECEBIMENTO'] || $produtos[$key + 1]['NUM_NOTA_FISCAL'] != $produto['NUM_NOTA_FISCAL'] || $produtos[$key + 1]['COD_SERIE_NOTA_FISCAL'] != $produto['COD_SERIE_NOTA_FISCAL']) {
                    $this->SetFont('Arial', 'B', 9);
                    $this->Cell(25, 5, "TOTAIS: ".$countProdutos, 0, 0, 'C');
                    $this->Cell(25, 5, "PRODUTOS: ", 0, 0, 'C');
                    $this->Cell(133, 5, $totalItem, 0, 0, 'C');
                    $this->Cell(-98, 5, $totalConferido, 0, 0, 'C');
                    $this->Cell(138, 5, $totalDivergencia, 0, 1, 'C');
                    $y1 = 35 + $y1 + ($countProdutos * 5);
                    $y2 = 35 + $y2 + ($countProdutos * 5);
                    $this->Ln();
                }
            } else if ($key + 1 == $numeroProdutos) {
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(25, 5, "TOTAIS: ".$countProdutos, 0, 0, 'C');
                $this->Cell(25, 5, "PRODUTOS: ", 0, 0, 'C');
                $this->Cell(133, 5, $totalItem, 0, 0, 'C');
                $this->Cell(-98, 5, $totalConferido, 0, 0, 'C');
                $this->Cell(138, 5, $totalDivergencia, 0, 1, 'C');
                $y1 = 35 + $y1 + ($countProdutos * 5);
                $y2 = 35 + $y2 + ($countProdutos * 5);
                $this->Ln();
            }

            $codRecebimentoAnterior = $produto['COD_RECEBIMENTO'];
            $notaFiscalAnterior = $produto['NUM_NOTA_FISCAL'];
            $serieAnterior = $produto['COD_SERIE_NOTA_FISCAL'];
            $codProdutoAnterior = $produto['COD_PRODUTO'];
            $dscGrade = $produto['DSC_GRADE'];
        }

        // page
        $this->Output('relatorio.pdf', 'D');
    }

}
