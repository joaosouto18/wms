<?php

namespace Wms\Module\Validade\Report;

use Core\Pdf;

class ProdutosAVencer extends Pdf
{
    private $pageW = 210;
    private $marginLeft = 7;
    private $prodListY = 20;
    private $lineH = 7;
    private $body;
    
    private function startPage($dataReferencia)
    {
        $this->SetMargins($this->marginLeft,5);
        $this->AddPage();
        $this->SetFont('Arial', 'B', 15);
        $this->Cell($this->body, 10, utf8_decode("Produtos vencidos ou à vencer até $dataReferencia"),0,0,"C");
    }

    private function addProdutoRow($produto, $i)
    { 
        $lineH = $this->lineH;

        // LINHA 1

        $this->SetFont('Arial', 'B', 10);
        $this->SetY($this->prodListY + ($i * (5 + (3 * $lineH))));
        $this->SetFillColor(170);
        $this->Cell(40, $lineH, utf8_decode("Produto: $produto[COD_PRODUTO]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 126;
        $str = self::setStringByMaxWidth(utf8_decode("Descrição: $produto[DESCRICAO]"),$cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(190);
        $this->Cell(30, $lineH, utf8_decode("Grade: $produto[GRADE]") ,1 ,1 ,'' , true);

        // LINHA 2

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 106;
        $str = self::setStringByMaxWidth(utf8_decode("Linha de separação: $produto[LINHA_SEPARACAO]"), $cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $this->Cell(40, $lineH, utf8_decode("Endereço: $produto[ENDERECO]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $strg = (!empty($produto['VALIDADE']))? $produto['VALIDADE'] : 'Sem Registro';
        $this->Cell(50, $lineH, utf8_decode("Data de validade: $strg") ,1 ,1 ,'' , true);

        //LINHA 3

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 120;
        $str = self::setStringByMaxWidth(utf8_decode("Fornecedor: $produto[FORNECEDOR]"), $cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $this->Cell(40, $lineH, utf8_decode("Qtd em estoque: $produto[QTD]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(175);

        $dt = date_create_from_format('d/m/Y', $produto['VALIDADE']) ;
        $now = date_create_from_format('d/m/Y', date('d/m/Y'));
        if (!empty($produto['VALIDADE']) && ($dt <= $now)){
            $status = "VENCIDO";
        } else if (!empty($produto['VALIDADE']) && ($dt > $now)) {
            $status = "À VENCER";
        } else {
            $status = 'N/D';
        }

        $this->Cell(36, $lineH, utf8_decode("STATUS: $status") ,1 ,0 ,'C' , true);
    }

    public function Footer()
    {

        $this->SetFont('Arial','',9);
        $this->SetY(-20);
        $this->Cell(176, 15, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(20, 15, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');
    }

    public function generatePDF($produtos, $dataReferencia)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->body = $this->pageW - (2 * $this->marginLeft);

        self::startPage($dataReferencia);
        $i = 0;

        foreach($produtos as $produto){
            if ($i > 9) {
                self::startPage($dataReferencia);
                $i = 0;
            }
            self::addProdutoRow($produto, $i);
            $i++;
        }

        self::Output('Produtos vencidos ou à vencer até '.$dataReferencia.'.pdf','D');
    }

}
