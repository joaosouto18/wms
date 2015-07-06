<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;
use Wms\Module\Web\Form\Deposito\Endereco;

class Inventario extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE INVENTARIO POR RUA" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20,  5, utf8_decode("Endereço")  ,1, 0);
        $this->Cell(15,  5, utf8_decode("Código")   ,1, 0);
        $this->Cell(90, 5, utf8_decode("Descrição") ,1, 0);
        $this->Cell(105, 5, utf8_decode("Volume") ,1, 0);
        $this->Cell(43, 5, utf8_decode("Unitizador") ,1, 0);
        $this->Cell(12,  5, "Qtde" ,1, 1);
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

    public function init($saldo,$exibirEstoque = false)
    {

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        $this->SetFont('Arial', 'B', 8);

        $enderecoAnterior = null;
        $codProdutoAnterior = null;
        $gradeAnterior = null;
        $descricaoVolume = null;
        $unitizadorAnterior = null;
        $qtdAnterior = null;
        $dscVolumes = "";
        $dscProdutoAnterior = null;

        if (count($saldo) >0) {
            $enderecoAnterior = $saldo[0]['dscEndereco'];
            $codProdutoAnterior = $saldo[0]['codProduto'];
            $dscProdutoAnterior = $saldo[0]['descricao'];
            $unitizadorAnterior = $saldo[0]['unitizador'];
            $gradeAnterior= $saldo[0]['grade'];
            $qtdAnterior = "";

            if ($exibirEstoque == true) {
                $qtdAnterior = $saldo[0]['qtd'];
            }

        }
        
        foreach ($saldo as $estoque) {
            $endereco = $estoque['dscEndereco'];
            $codProduto = $estoque['codProduto'];
            $dscProduto = $estoque['descricao'];
            $descricaoVolume = str_replace(";CADASTRO","",$estoque['volume']);
            $unitizador = $estoque['unitizador'];
            $grade= $estoque['grade'];
            $qtd = "";

            if ($exibirEstoque == true) {
                $qtd = $estoque['qtd'];
            }

            if (($endereco != $enderecoAnterior) || ($codProduto != $codProdutoAnterior) || ($grade != $gradeAnterior) || ($qtd != $qtdAnterior) || ($unitizadorAnterior != $unitizador || $dscProdutoAnterior != $dscProdutoAnterior)) {
                if (strlen($dscVolumes) >=63) {
                    $dscVolumes = substr($dscVolumes,0,59) . "...";
                }

                if ($qtdAnterior == 0) {
                    $dscVolumes = "";
                    $estoque['unitizador'] = "";
                }

                $this->Cell(20,5, $enderecoAnterior ,1, 0);
                $this->Cell(15, 5, $codProdutoAnterior ,1, 0);
                $this->Cell(90, 5, substr($dscProdutoAnterior, 0, 50) ,1, 0);
                $this->Cell(105, 5, $dscVolumes ,1, 0);
                $this->Cell(43, 5, $unitizadorAnterior ,1, 0);
                $this->Cell(12, 5, $qtdAnterior ,1, 1);

                $dscVolumes = "";
            }

            if ($dscVolumes != "") $dscVolumes.=";";
            $dscVolumes .= $descricaoVolume;

            $enderecoAnterior = $endereco;
            $codProdutoAnterior = $codProduto;
            $gradeAnterior = $grade;
            $dscProdutoAnterior = $dscProduto;
            $unitizadorAnterior = $unitizador;
            $qtdAnterior = $qtd;


            if ($estoque == $saldo[count($saldo)-1]){

                if (strlen($dscVolumes) >=63) {
                    $dscVolumes = substr($dscVolumes,0,59) . "...";
                }

                $this->Cell(20,5,$enderecoAnterior ,1, 0);
                $this->Cell(15, 5, $codProdutoAnterior ,1, 0);
                $this->Cell(90, 5, substr($dscProdutoAnterior,0,50) ,1, 0);
                $this->Cell(105, 5, $dscVolumes ,1, 0);
                $this->Cell(43, 5, $unitizadorAnterior ,1, 0);
                $this->Cell(12, 5, $qtdAnterior ,1, 1);
            }
        }
        $this->Output('Inventario-Por-Rua.pdf','D');
    }
}
