<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf,
    Wms\Domain\Entity\Expedicao\VRelProdutosRepository;

class ProdutosSemConferencia extends Pdf
{
    protected $idExpedicao;
    protected $placa;

    public function Header()
    {

        $plc = null;
        if (!empty($this->placa)) $plc = " ( $this->placa )";
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE PRODUTOS PENDENTES DE CONFERÊNCIA - EXPEDIÇÃO $this->idExpedicao $plc" ), 0, 1);

    }

    public function Footer()
    {
        // font
        $this->SetFont('Arial','B',7);

        //Go to 1.5 cm from bottom
        $this->SetY(-20);

        $this->Cell(270, 10, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        // font
        $this->SetFont('Arial','',8);
        $this->Cell(0,15,utf8_decode('Página ').$this->PageNo(),0,0,'R');
    }

    public function imprimir($idExpedicao, $produtos, $modelo = 1, $quebraCarga = "N", $placa = null, $usaGrade = true)
    {
        $this->idExpedicao = $idExpedicao;
        $this->placa = $placa;

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);

       switch ($modelo) {
           case 2:
               $this->layout2($produtos, $quebraCarga, $usaGrade);
               break;
           default:
               $this->layout1($produtos, $quebraCarga, $usaGrade);
       }

        $this->Output('Produtos-Sem_Conferencia-'.$idExpedicao.'.pdf','D');
    }

    private function layout1($produtos, $quebraCarga, $usaGrade = true) {

        $cargaAntiga = "";
        /** @var \Wms\Domain\Entity\Produto $produto */
        $this->AddPage();

        if (!$usaGrade) {
            $wCols['pedido'] = 20;
            $wCols['etiqueta'] = 23;
            $wCols['produto'] = 19;
            $wCols['descricao'] = 100;
            $wCols['volume'] = 23;
            $wCols['cliente'] = 55;
            $wCols['carga'] = 20;
            $wCols['estoque'] = 20;
        } else {
            $wCols['pedido'] = 18;
            $wCols['etiqueta'] = 15;
            $wCols['produto'] = 14;
            $wCols['descricao'] = 61;
            $wCols['grade'] = 54;
            $wCols['volume'] = 23;
            $wCols['cliente'] = 55;
            $wCols['carga'] = 20;
            $wCols['estoque'] = 20;
        }

        foreach($produtos as $key => $produto) {
            $novaCarga = utf8_decode($produto["codCargaExterno"]);

            if ($novaCarga != $cargaAntiga) {
                $this->ln(2);
                $this->Cell($wCols['pedido']   , 5, "Pedido", "TB");
                $this->Cell($wCols['etiqueta'] , 5, "Etiqueta", "TB");
                $this->Cell($wCols['produto']  , 5, "Produto", "TB");
                $this->Cell($wCols['descricao'], 5, utf8_decode("Descrição"), "TB");
                if ($usaGrade)
                    $this->Cell($wCols['grade']    , 5, "Grade", "TB");
                $this->Cell($wCols['volume']   , 5, "Volume", "TB");
                $this->Cell($wCols['cliente']  , 5, "Cliente", "TB");
                $this->Cell($wCols['carga']    , 5, "Carga", "TB");
                $this->Cell($wCols['estoque']  , 5, "Estoque", "TB");
                $this->Ln();
            }

            $this->Cell($wCols['pedido']   , 5, utf8_decode($produto["pedido"]) , 0);
            $this->Cell($wCols['etiqueta'] , 5, utf8_decode($produto["codBarras"]) , 0);
            $this->Cell($wCols['produto']  , 5, utf8_decode($produto["codProduto"])  , 0);
            $this->Cell($wCols['descricao'], 5, substr(utf8_decode($produto["produto"]),0,30), 0);
            if ($usaGrade)
                $this->Cell($wCols['grade']    , 5, substr(utf8_decode($produto["grade"]),0,30), 0);
            $this->Cell($wCols['volume']   , 5, utf8_decode($produto["embalagem"])    , 0);
            $this->Cell($wCols['cliente']  , 5, substr(utf8_decode($produto["cliente"]),0,30), 0);
            $this->Cell($wCols['carga']    , 5, utf8_decode($produto["codCargaExterno"])  , 0);
            $this->Cell($wCols['estoque']  , 5, utf8_decode($produto["codEstoque"]), 0);
            $cargaAntiga = $novaCarga;
            $this->Ln();
        }
    }
    private function layout2($produtos, $quebraCarga, $usaGrade = true){
        $cargaAntiga = "";

        $this->AddPage();
        /** @var \Wms\Domain\Entity\Produto $produto */
        foreach($produtos as $key => $produto) {
            $novaCarga = utf8_decode($produto["codCargaExterno"]);
            if ($novaCarga != $cargaAntiga) {
                $this->ln(2);

                $this->Cell(13, 5, "Pedido", "TB");
                $this->Cell(20, 5, "Etiqueta", "TB");
                $this->Cell(14, 5, "Produto", "TB");
                $this->Cell(90, 5, utf8_decode("Descrição"), "TB");
                $this->Cell(40, 5, "Volume", "TB");
                $this->Cell(51, 5, "Cliente", "TB");
                $this->cell(20 ,5, utf8_decode("Endereço"),"TB");
                $this->Cell(15, 5, "Carga", "TB");
                $this->Cell(15, 5, "Estoque", "TB");
                $this->Ln();
            }

            $this->Cell(13, 5, utf8_decode($produto["pedido"]) , 0);
            $this->Cell(20, 5, utf8_decode($produto["codBarras"]) , 0);
            $this->Cell(14, 5, utf8_decode($produto["codProduto"])  , 0);
            $this->Cell(90, 5, $this->SetStringByMaxWidth(utf8_decode($produto["produto"]),90), 0);
            $this->Cell(40, 5, $this->SetStringByMaxWidth(utf8_decode($produto["embalagem"]), 40), 0);
            $this->Cell(51, 5, $this->SetStringByMaxWidth(utf8_decode($produto["cliente"]), 51), 0);
            $this->Cell(20, 5, utf8_decode($produto["endereco"])  , 0);
            $this->Cell(15, 5, utf8_decode($produto["codCargaExterno"])  , 0);
            $this->Cell(15, 5, utf8_decode($produto["codEstoque"]), 0);
            $cargaAntiga = $novaCarga;

            $this->Ln();
        }
    }
}
