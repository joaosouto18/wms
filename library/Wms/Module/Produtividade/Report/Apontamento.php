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
    private $offsetHead = 10;
    private $offsetListW = 12.5;
    private $colIndexHeadW = 77.5;
    private $colIndexW = 75;
    private $colProdutoW = 30;
    private $colCubagemW= 25;
    private $colPesoW = 19;
    private $colVolumesW = 22;
    private $colPaletesW = 20;
    private $dataInicio;
    private $dataFim;
    private $orientacao;
    private $startY = 27;

    public function generatePDF($params)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->marginLeft = 7;
        $this->marginRight = $this->GetPageWidth() - $this->marginLeft;
        $this->body = $this->pageW - (2 * $this->marginLeft);

        $this->dataInicio = $params['dataInicio'];
        $this->dataFim = $params['dataFim'];
        $this->orientacao = $params['orientacao'];

        $startY = self::startPage();

        foreach($params['rows'] as $index => $rows){
            $lastY = self::addGroup($params['orientacao'], $index, $rows, $startY);
            $startY = ($lastY + 5);
        }

        self::Output('Relatorio de Produtividade por '.$params['orientacao'].'.pdf','D');
    }

    private function startPage()
    {
        $marginL = $this->marginLeft;
        $marginR = $this->marginRight;
        $orientacao = $this->orientacao;

        $this->SetMargins($marginL,5);
        $this->AddPage();
        $this->SetLineWidth(1);
        $this->SetDrawColor(12, 53, 140);

        $this->Line($marginL, 3, $marginR, 3);
        //$this->Image(APPLICATION_PATH . '\..\public\img\admin\logoRelatorio.gif', 18, 7, 28, 11);

        $this->SetY(4);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell($this->body, 6, utf8_decode("Relatório de produtividade"),0,1,"C");
        $this->Cell($this->body, 6, utf8_decode("Agrupado por $orientacao"),0,1,"C");

        $this->SetFont('Arial', '', 12);
        $dataInicio = $this->dataInicio;
        $dataFim = $this->dataFim;
        $this->Cell($this->body, 6, utf8_decode("Período de $dataInicio até $dataFim"),0,1,"C");

        $this->Image(APPLICATION_PATH .'\..\public\img\logo_quebec.jpg',160,6,40,12);
        $this->Line($marginL, 23, $marginR, 23);

        return $this->startY;
    }

    private function addGroup($orientacao, $groupIndex, $rowsGroup, $startY)
    {
        $keyRow = ($orientacao == 'Atividade')? 'NOM_PESSOA' : 'DSC_ATIVIDADE';
        $headGroup = ($orientacao == 'Atividade')? 'Funcionário' : 'Atividade';
        $lineH = $this->lineH;
        $marginL = $this->marginLeft;
        $marginR = $this->marginRight;

        $groupHeadH = 16;
        $groupEndH = 7;

        $footerH = 18;
        $posicaoAtual = $this->GetY();
        $pageH = (int) $this->GetPageHeight();
        $heightRestante = $pageH - $posicaoAtual - $footerH;

        if ($groupHeadH > $heightRestante) {
            $startY = self::startPage();
        }

        self::startGroup($startY, $groupIndex, $headGroup);

        $this->SetFont('Arial','',9);
        $startYGroup = $startY + $groupHeadH;
        $this->SetY($startYGroup);

        $i = 1;
        $tItens = 0;
        $tVolumes = 0;
        $tCubagem = 0;
        $tPeso = 0;
        $tPalete = 0;
        foreach ($rowsGroup as $key => $row) {

            $qtdProduto = (!empty($row['QTD_PRODUTOS']))?$row['QTD_PRODUTOS']:0;
            $tItens += $qtdProduto;

            $qtdCubagem = (!empty($row['QTD_CUBAGEM']))?$row['QTD_CUBAGEM']:0;
            $tCubagem += $qtdCubagem;

            $qtdPeso = (!empty($row['QTD_PESO']))?$row['QTD_PESO']:0;
            $tPeso += $qtdPeso;

            $qtdVolumes = (!empty($row['QTD_VOLUMES']))?$row['QTD_VOLUMES']:0;
            $tVolumes += $qtdVolumes;

            $qtdPaletes = (!empty($row['QTD_PALETES']))?$row['QTD_PALETES']:0;
            $tPalete += $qtdPaletes;

            $posicaoAtual = $this->GetY();
            $heightRestante = $pageH - $posicaoAtual - $footerH - ($lineH + $groupEndH);

            if ($heightRestante < 0) {
                $startY = self::startPage();
                self::startGroup($startY, $groupIndex, $headGroup);
                $this->SetFont('Arial','',9);
                $startYGroup = $startY + $groupHeadH;
                $this->SetY($startYGroup);
                $i = 1;
            }

            self::addListRow($lineH, $row[$keyRow], $qtdProduto, $qtdCubagem, $qtdPeso, $qtdVolumes, $qtdPaletes);

            $endLineY = $startYGroup + ($lineH * $i);
            $this->Line($marginL + 10, $endLineY, $marginR, $endLineY);
            $i++;
        }

        self::endGroup($lineH, $tItens, $tCubagem, $tPeso, $tVolumes, $tPalete, $i, $startYGroup, $marginL, $marginR);

        return $this->GetY();
    }

    private function addListRow($lineH, $index, $qtdProduto, $qtdCubagem, $qtdPeso, $qtdVolumes, $qtdPaletes)
    {
        $this->Cell($this->offsetListW, $lineH);
        $cellWidth = $this->colIndexW;
        $str = self::setStringByMaxWidth(utf8_decode($index),$cellWidth);
        $this->Cell($cellWidth, $lineH, $str,0,0);
        $this->Cell($this->colProdutoW, $lineH, $qtdProduto,0,0);
        $this->Cell($this->colCubagemW, $lineH, $qtdCubagem,0,0);
        $this->Cell($this->colPesoW, $lineH, $qtdPeso,0,0);
        $this->Cell($this->colVolumesW, $lineH, $qtdVolumes,0,0);
        $this->Cell($this->colPaletesW, $lineH, $qtdPaletes,0,1);
    }

    private function startGroup($startY, $groupIndex, $headGroup)
    {
        $sergH = $this->startEndRowGroupH;
        $marginL = $this->marginLeft;
        $marginR = $this->marginRight;
        $orientacao = $this->orientacao;

        $this->SetY($startY);
        $this->SetFont('Arial', '',10);
        $this->Cell(10,$sergH);
        $this->Cell(20, $sergH, utf8_decode("$orientacao:"));
        $this->Cell(80, $sergH, utf8_decode($groupIndex), 0, 1);

        $this->SetLineWidth(0.5);
        $this->Line($marginL, $startY + 1 + $sergH, $marginR,  $startY + 1 + $sergH);

        $this->SetY($startY + 2 + $sergH);
        $this->SetFont('Arial', 'B',9);
        $this->Cell($this->offsetHead);
        $this->Cell($this->colIndexHeadW, $sergH, utf8_decode($headGroup));
        $this->Cell($this->colProdutoW, $sergH, utf8_decode("Qtde produtos"));
        $this->Cell($this->colCubagemW, $sergH, utf8_decode("Cubagem"));
        $this->Cell($this->colPesoW, $sergH, utf8_decode("Peso"));
        $this->Cell($this->colVolumesW, $sergH, utf8_decode("Volumes"));
        $this->Cell($this->colPaletesW, $sergH, utf8_decode("Paletes"),0,1);
    }

    private function endGroup($lineH, $tItens, $tCubagem, $tPeso, $tVolumes, $tPalete, $i, $startYGroup, $marginL, $marginR)
    {
        $this->SetFont('Arial','B',11);
        $this->Cell($this->offsetListW, $lineH);
        $this->Cell($this->colIndexW, $lineH, 'TOTAL',0,0);
        $this->Cell($this->colProdutoW, $lineH, $tItens,0,0);
        $this->Cell($this->colCubagemW, $lineH, $tCubagem,0,0);
        $this->Cell($this->colPesoW, $lineH, $tPeso,0,0);
        $this->Cell($this->colVolumesW, $lineH, $tVolumes,0,0);
        $this->Cell($this->colPaletesW, $lineH, $tPalete,0,1);

        $endGroupY = $startYGroup + ($lineH * $i) ;
        $this->Line($marginL, $endGroupY, $marginR, $endGroupY);
    }

    public function Footer()
    {
        $this->SetFont('Arial','',9);
        $this->SetY(-20);
        $this->Cell(176, 15, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(20, 15, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');
    }
}
