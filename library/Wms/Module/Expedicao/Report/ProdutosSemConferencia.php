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

    public function imprimir($idExpedicao, $produtos, $modelo = 1, $quebraCarga = "N", $placa = null)
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
               $this->layout2($produtos, $quebraCarga);
               break;
           default:
               $this->layout1($produtos, $quebraCarga);
       }

        $this->Output('Produtos-Sem_Conferencia-'.$idExpedicao.'.pdf','D');
    }

    private function layout1($produtos, $quebraCarga) {

        $cargaAntiga = "";
        /** @var \Wms\Domain\Entity\Produto $produto */
        $this->AddPage();
        foreach($produtos as $key => $produto) {
            $novaCarga = utf8_decode($produto["codCargaExterno"]);

            if ($novaCarga != $cargaAntiga) {
                $this->ln(2);
                $this->Cell(18, 5, "Pedido", "TB");
                $this->Cell(15, 5, "Etiqueta", "TB");
                $this->Cell(14, 5, "Produto", "TB");
                $this->Cell(61, 5, utf8_decode("Descrição"), "TB");
                $this->Cell(54, 5, "Grade", "TB");
                $this->Cell(23, 5, "Volume", "TB");
                $this->Cell(55, 5, "Cliente", "TB");
                $this->Cell(20, 5, "Carga", "TB");
                $this->Cell(20, 5, "Estoque", "TB");
                $this->Ln();
            }

            $this->Cell(18, 5, utf8_decode($produto["pedido"]) , 0);
            $this->Cell(15, 5, utf8_decode($produto["codBarras"]) , 0);
            $this->Cell(14, 5, utf8_decode($produto["codProduto"])  , 0);
            $this->Cell(61, 5, substr(utf8_decode($produto["produto"]),0,30), 0);
            $this->Cell(54, 5, substr(utf8_decode($produto["grade"]),0,30), 0);
            $this->Cell(23, 5, utf8_decode($produto["embalagem"])    , 0);
            $this->Cell(55, 5, substr(utf8_decode($produto["cliente"]),0,30), 0);
            $this->Cell(20, 5, utf8_decode($produto["codCargaExterno"])  , 0);
            $this->Cell(20, 5, utf8_decode($produto["codEstoque"]), 0);
            $cargaAntiga = $novaCarga;
            $this->Ln();
        }
    }
    private function layout2($produtos, $quebraCarga){
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
