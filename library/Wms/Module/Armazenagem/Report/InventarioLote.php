<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class InventarioLote extends Pdf
{

    private $usaGrade;
    private $wColEnd = 20;
    private $wColCod = 15;
    private $wColDesc = 80;
    private $wColGrad = 50;
    private $wColVol = 50;
    private $wColUnit = 40;
    private $wColQtd = 20;
    private $wColLote = 12;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE INVENTARIO POR RUA" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell($this->wColEnd,  5, utf8_decode("Endereço")  ,1, 0);
        $this->Cell($this->wColCod,  5, utf8_decode("Código")   ,1, 0);
        $this->Cell($this->wColDesc, 5, utf8_decode("Descrição") ,1, 0);

        if ($this->usaGrade)
            $this->Cell($this->wColGrad, 5, utf8_decode("Grade") ,1, 0);

        $this->Cell($this->wColVol, 5, utf8_decode("Volume") ,1, 0);
        $this->Cell($this->wColUnit, 5, utf8_decode("Unitizador") ,1, 0);
        $this->Cell($this->wColLote, 5, utf8_decode("Lote") ,1, 0);
        $this->Cell($this->wColQtd,  5, "Qtde" ,1, 1);
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

    public function init($saldo,$exibirEstoque = false, $usaGrade = false)
    {

        $fator = ($this->GetPageWidth() / 40);

        $this->wColEnd = 4 * $fator;
        $this->wColCod = 3 * $fator;
        if ($usaGrade) {
            $this->wColDesc = 11 * $fator;
            $this->wColGrad = 4 * $fator;
        } else {
            $this->wColDesc = 17 * $fator;
        }
        $this->wColVol = 4 * $fator;
        $this->wColUnit = 4 * $fator;
        $this->wColLote = 4 * $fator;
        $this->wColQtd = 2 * $fator;

        $this->usaGrade = $usaGrade;
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->AddPage();
        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);

        $enderecoAnterior = null;
        $codProdutoAnterior = null;
        $gradeAnterior = null;
        $descricaoVolume = null;
        $unitizadorAnterior = null;
        $loteAnterior = null;
        $qtdAnterior = null;
        $dscVolumes = "";
        $dscProdutoAnterior = null;

        if (count($saldo) >0) {
            $enderecoAnterior = $saldo[0]['dscEndereco'];
            $codProdutoAnterior = $saldo[0]['codProduto'];
            $dscProdutoAnterior = $saldo[0]['descricao'];
            $unitizadorAnterior = $saldo[0]['unitizador'];
            $loteAnterior = $saldo[0]['lote'];
            $gradeAnterior = $this->SetStringByMaxWidth($saldo[0]['grade'], $this->wColGrad);
            $qtdAnterior = "";

            if ($exibirEstoque == true) {
                $qtdAnterior = $saldo[0]['qtd'];
            }
        }

        foreach ($saldo as $estoque) {
            $endereco = $estoque['dscEndereco'];
            $codProduto = $estoque['codProduto'];
            $dscProduto = str_replace('  ',' ',$estoque['descricao']);
            $descricaoVolume = str_replace(";CADASTRO","",$estoque['volume']);
            $lote = $estoque['lote'];
            $unitizador = $estoque['unitizador'];
            $grade = $this->SetStringByMaxWidth($estoque['grade'], $this->wColGrad);
            $qtd = "";

            if ($exibirEstoque == true) {
                $qtd = $estoque['qtd'];
            }

            if (($endereco != $enderecoAnterior) || ($codProduto != $codProdutoAnterior) || ($grade != $gradeAnterior) || ($qtd != $qtdAnterior) || ($lote != $loteAnterior) || ($unitizadorAnterior != $unitizador || $dscProdutoAnterior != $dscProdutoAnterior)) {
                $dscVolumes = $this->SetStringByMaxWidth($dscVolumes, $this->wColVol);

                if ($qtdAnterior == 0) {
                    $dscVolumes = "";
                    $estoque['unitizador'] = "";
                }

                $this->Cell($this->wColEnd,5, $enderecoAnterior, 1, 0);
                $this->Cell($this->wColCod, 5, $codProdutoAnterior, 1, 0);
                $this->Cell($this->wColDesc, 5, str_replace('  ',' ',$dscProdutoAnterior),1, 0);
                if ($this->usaGrade)
                    $this->Cell($this->wColGrad, 5, $gradeAnterior, 1, 0);
                $this->Cell($this->wColVol, 5, $dscVolumes, 1, 0);
                $this->Cell($this->wColUnit, 5, $unitizadorAnterior, 1, 0);
                $this->Cell($this->wColLote, 5, $loteAnterior, 1, 0);
                $this->Cell($this->wColQtd, 5, $qtdAnterior, 1, 1);

                $dscVolumes = "";
            }

            if ($dscVolumes != "") $dscVolumes.=";";
            $dscVolumes .= $descricaoVolume;

            $enderecoAnterior = $endereco;
            $codProdutoAnterior = $codProduto;
            $gradeAnterior = $grade;
            $dscProdutoAnterior = $dscProduto;
            $unitizadorAnterior = $unitizador;
            $loteAnterior = $lote;
            $qtdAnterior = $qtd;


            if ($estoque == $saldo[count($saldo)-1]){

                if (strlen($dscVolumes) >=63) {
                    $dscVolumes = $this->SetStringByMaxWidth($dscVolumes, $this->wColVol);
                }

                $this->Cell($this->wColEnd,5,$enderecoAnterior ,1, 0);
                $this->Cell($this->wColCod, 5, $codProdutoAnterior ,1, 0);
                $this->Cell($this->wColDesc, 5, str_replace('  ',' ',$dscProdutoAnterior),1, 0);
                if ($this->usaGrade)
                    $this->Cell($this->wColGrad, 5, $gradeAnterior, 1, 0);
                $this->Cell($this->wColVol, 5, $dscVolumes ,1, 0);
                $this->Cell($this->wColUnit, 5, $unitizadorAnterior ,1, 0);
                $this->Cell($this->wColLote, 5, $loteAnterior, 1, 0);
                $this->Cell($this->wColQtd, 5, $qtdAnterior ,1, 1);
            }
        }
        $this->Output('Inventario-Por-Rua.pdf','D');
    }
}
