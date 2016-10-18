<?php

namespace Wms\Module\Produtividade\Report;

use Core\Pdf;

class Apontamento extends Pdf
{
    private $pageW = 210;
    private $marginLeft = 0;
    private $marginRight = 0;
    private $startEndRowGroupH = 8;
    private $lineH = 6;
    private $body;
    
    private function startPage($orientacao, $dataInicio, $dataFim)
    {
        $marginL = $this->marginLeft;
        $marginR = $this->marginRight;
        $this->SetMargins($marginL,5);
        $this->AddPage();
        $this->SetLineWidth(1);
        $this->SetDrawColor(12, 53, 140);

        $this->Line($marginL, 3, $marginR, 3);
        $this->Image(APPLICATION_PATH . '\..\public\img\admin\logoRelatorio.gif', 18, 7, 28, 11);

        $this->SetY(4);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell($this->body, 6, utf8_decode("Relatório de produtividade"),0,1,"C");
        $this->Cell($this->body, 6, utf8_decode("Agrupado por $orientacao"),0,1,"C");

        $this->SetFont('Arial', '', 12);
        $this->Cell($this->body, 6, utf8_decode("Período de $dataInicio até $dataFim"),0,1,"C");

        $this->Image(APPLICATION_PATH .'\..\public\img\logo_quebec.jpg',160,6,40,12);
        $this->Line($marginL, 23, $marginR, 23);
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

    public function generatePDF($params)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->marginLeft = 7;
        $this->marginRight = $this->GetPageWidth() - $this->marginLeft;
        $this->body = $this->pageW - (2 * $this->marginLeft);

        $sergH = $this->startEndRowGroupH;
        $lineH = $this->lineH;
        $pgH = $this->GetPageHeight();
        $startY = 27;

        self::startPage($params['orientacao'], $params['dataInicio'], $params['dataFim']);

        $pgHeightRestante = $pgH;

        foreach($params['rows'] as $index => $rows){
            $groupH = (count($rows) * $lineH) + ($sergH * 3);
            if ($groupH < $pgHeightRestante) {
                self::addFullGroup($params['orientacao'], $index, $rows, $startY);
            }
            $startY += ($groupH + 5);
            $pgHeightRestante -= $groupH;

        }

        self::Output('Relatorio de Produtividade por '.$params['orientacao'].'.pdf','D');
    }

    private function addFullGroup($orientacao, $groupIndex, $rowsGroup, $startY)
    {

        $keyRow = ($orientacao == 'Atividade')? 'NOM_PESSOA' : 'DSC_ATIVIDADE';
        $headGroup = ($orientacao == 'Atividade')? 'Funcionário' : 'Atividade';
        $tItens = 0;
        $tVolumes = 0;
        $tCubagem = 0;
        $tPeso = 0;
        $tPalete = 0;
        $lineH = $this->lineH;
        $sergH = $this->startEndRowGroupH;
        $marginL = $this->marginLeft;
        $marginR = $this->marginRight;

        $this->SetY($startY);
        $this->SetFont('Arial', '',10);
        $this->Cell(10,$sergH);
        $this->Cell(20, $sergH, utf8_decode("$orientacao:"));
        $this->Cell(80, $sergH, utf8_decode($groupIndex), 0, 1);

        $this->SetLineWidth(0.5);

        $this->Line($marginL, $startY + 1 + $sergH, $marginR,  $startY + 1 + $sergH);

        $this->SetY($startY + 2 + $sergH);
        $this->SetFont('Arial', 'B',9);
        $this->Cell(10);
        $this->Cell(65, $sergH, utf8_decode($headGroup));
        $this->Cell(30, $sergH, utf8_decode("Qtde produtos"));
        $this->Cell(35, $sergH, utf8_decode("Cubagem"));
        $this->Cell(25, $sergH, utf8_decode("Peso"));
        $this->Cell(20, $sergH, utf8_decode("Volumes"));
        $this->Cell(20, $sergH, utf8_decode("Paletes"));

        $this->SetFont('Arial','',9);
        $startYGroup = $startY + 16;
        $this->SetY($startYGroup);

        $i = 1;
        foreach ($rowsGroup as $row) {
            $this->Cell(15, $lineH);
<<<<<<< HEAD
            $cellWidth = 40;
            $str = self::setStringByMaxWidth(utf8_decode($row),$cellWidth);
            //$this->Cell($cellWidth, $lineH, )
=======
            $cellWidth = 65;
            $str = self::setStringByMaxWidth(utf8_decode($row[$keyRow]),$cellWidth);
            $this->Cell($cellWidth, $lineH, $str,0,0);

            $qtd = (!empty($row['QTD_PRODUTOS']))?$row['QTD_PRODUTOS']:0;
            $tItens += $qtd;
            $this->Cell(30, $lineH, $qtd,0,0);

            $qtd = (!empty($row['QTD_CUBAGEM']))?$row['QTD_CUBAGEM']:0;
            $tCubagem += $qtd;
            $this->Cell(35, $lineH, $row['QTD_CUBAGEM'],0,0);

            $qtd = (!empty($row['QTD_PESO']))?$row['QTD_PESO']:0;
            $tPeso += $qtd;
            $this->Cell(20, $lineH, $row['QTD_PESO'],0,0);

            $qtd = (!empty($row['QTD_VOLUMES']))?$row['QTD_VOLUMES']:0;
            $tVolumes += $qtd;
            $this->Cell(25, $lineH, $row['QTD_VOLUMES'],0,0);

            $qtd = (!empty($row['QTD_PALETES']))?$row['QTD_PALETES']:0;
            $tPalete += $qtd;
            $this->Cell(20, $lineH, $row['QTD_PALETES'],0,1);


            $endLineY = $startYGroup + ($lineH * $i);
            $this->Line($marginL + 10, $endLineY, $marginR, $endLineY);
            $i++;
>>>>>>> f1bd0083d47fc784d6bba6c0cdb659eadf161a74
        }
        $this->SetFont('Arial','B',11);
        $this->Cell(15, $lineH);
        $this->Cell(65, $lineH, 'TOTAL',0,0);
        $this->Cell(30, $lineH, $tItens,0,0);
        $this->Cell(35, $lineH, $tCubagem,0,0);
        $this->Cell(20, $lineH, $tPeso,0,0);
        $this->Cell(25, $lineH, $tVolumes,0,0);
        $this->Cell(20, $lineH, $tPalete,0,1);

        $endGroupY = $startYGroup + ($lineH * $i) ;
        $this->Line($marginL, $endGroupY, $marginR, $endGroupY);
    }

    private function addPartialGroup($orientacao, $groupIndex, $rowsGroup, $y, $endRow)
    {

    }
}
