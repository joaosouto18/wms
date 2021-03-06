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
    private $colProdutoW = 24;
    private $colCubagemW= 27;
    private $colPesoW = 24;
    private $colVolumesW = 24;
    private $colPaletesW = 24;
    private $colCargasW = 24;
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

        //$this->Image(APPLICATION_PATH .'\..\public\img\logo_quebec.jpg',160,6,40,12);
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

        $groupHeadH = 21;
        $groupEndH = 7;

        $footerH = 21;
        $posicaoAtual = $this->GetY();
        $pageH = (int) $this->GetPageHeight();
        $heightRestante = $pageH - $posicaoAtual - $footerH;

        if (($groupHeadH + $lineH + 1) > $heightRestante) {
            $startY = self::startPage();
        }

        $rowBreak = null;
        $check = self::checkPageBreak($rowsGroup, $groupHeadH, $lineH, $startY);
        if (!empty($check))
            list($startY, $rowBreak) = $check;

        self::startGroup($startY, $groupIndex, $headGroup);

        $this->SetFont('Arial','',8);
        $startYGroup = $startY + $groupHeadH - 5;
        $this->SetY($startYGroup);

        $i = 1;
        $tItens = 0;
        $tVolumes = 0;
        $tCubagem = 0;
        $tPeso = 0;
        $tPalete = 0;
        $tCarga = 0;
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

            $qtdCarga = (!empty($row['QTD_CARGA'])) ? $row['QTD_CARGA'] : 0;
            $tCarga += $qtdCarga;

            /*$posicaoAtual = $this->GetY();
            $next = (isset($rowsGroup[$key + 1]))? $lineH : $lineH + $groupEndH;
            $heightRestante = $pageH - $posicaoAtual - $footerH - $next;*/

            if (!is_null($rowBreak) && $key == $rowBreak) {
                $startY = self::startPage();
                self::startGroup($startY, $groupIndex, $headGroup);
                $this->SetFont('Arial','',8);
                $startYGroup = $startY + $groupHeadH - 5;
                $this->SetY($startYGroup);
                $i = 1;
            }

            self::addListRow($lineH, $row[$keyRow], $qtdProduto, $qtdCubagem, $qtdPeso, $qtdVolumes, $qtdPaletes, $qtdCarga);

            $endLineY = $startYGroup + ($lineH * $i);
            $this->Line($marginL + 10, $endLineY, $marginR, $endLineY);
            $i++;
        }

        self::endGroup($lineH, $tItens, $tCubagem, $tPeso, $tVolumes, $tPalete, $i, $startYGroup, $marginL, $marginR, $tCarga);

        return $this->GetY();
    }

    private function addListRow($lineH, $index, $qtdProduto, $qtdCubagem, $qtdPeso, $qtdVolumes, $qtdPaletes, $qtdCarga)
    {
        $this->Cell($this->offsetListW, $lineH);
        $cellWidth = $this->colIndexW;
        $str = self::SetStringByMaxWidth(utf8_decode($index),$cellWidth);
        $this->Cell($cellWidth, $lineH, $str,0,0);
        $this->Cell($this->colProdutoW, $lineH, number_format($qtdProduto,2),0,0);
        //$this->Cell($this->colCubagemW, $lineH, $qtdCubagem,0,0);
        $this->Cell($this->colPesoW, $lineH, number_format($qtdPeso,2),0,0);
        $this->Cell($this->colVolumesW, $lineH, number_format($qtdVolumes,2),0,0);
        $this->Cell($this->colPaletesW, $lineH, number_format($qtdPaletes,2),0,0);
        $this->Cell($this->colCargasW, $lineH, number_format($qtdCarga,2),0,1);
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
        $this->Cell($this->colProdutoW, $sergH, utf8_decode("Produtos"));
        //$this->Cell($this->colCubagemW, $sergH, utf8_decode("Cubagem"));
        $this->Cell($this->colPesoW, $sergH, utf8_decode("Peso"));
        $this->Cell($this->colVolumesW, $sergH, utf8_decode("Volumes"));
        $this->Cell($this->colPaletesW, $sergH, utf8_decode("Paletes"));
        $this->Cell($this->colCargasW, $sergH, 'Cargas',0,1);
    }

    private function endGroup($lineH, $tItens, $tCubagem, $tPeso, $tVolumes, $tPalete, $i, $startYGroup, $marginL, $marginR, $tCarga)
    {
        $this->SetFont('Arial','B',10);
        $this->Cell($this->offsetListW, $lineH);
        $this->Cell($this->colIndexW, $lineH, 'TOTAL',0,0);
        $this->Cell($this->colProdutoW, $lineH, number_format($tItens,2),0,0);
        //$this->Cell($this->colCubagemW, $lineH, $tCubagem,0,0);
        $this->Cell($this->colPesoW, $lineH, number_format($tPeso,2),0,0);
        $this->Cell($this->colVolumesW, $lineH, number_format($tVolumes,2),0,0);
        $this->Cell($this->colPaletesW, $lineH, number_format($tPalete,2),0,0);
        $this->Cell($this->colCargasW, $lineH, number_format($tCarga,2),0,1);

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

    private function checkPageBreak($rows, $groupHeadH, $lineH, $startY)
    {
        $groupEndH = 7;
        $footerH = 21;
        $posicaoAtual = $this->GetY();
        $pageH = (int) $this->GetPageHeight();

        $r = count($rows);

        if ($r < 2) {
            $heightRestante = $pageH - $posicaoAtual - ($groupHeadH + $lineH + $groupEndH) - $footerH;
            if ($heightRestante < 0) {
                $startY = self::startPage();
                return array($startY, null);
            }
            return null;
        } else {
            $k = 1;
            while ($k <= $r){
                $next = ($k < $r)?$k * $lineH : ($k * $lineH) + $footerH;
                $heightRestante = $pageH - $posicaoAtual - ($groupHeadH  + $next) - $footerH;
                if ($heightRestante < 0) {
                    return array($startY, ($k - 1));
                }
                $k++;
            }
            return null;
        }
    }
}
