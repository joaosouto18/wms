<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class DisponibilidadeEstoque extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIOD DE DISPONIBILIDADE DE ENDEREÇO" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20,  5, utf8_decode("Endereço")  ,1, 0);
        $this->Cell(20,  5, utf8_decode("Código")   ,1, 0);
        $this->Cell(25,  5, utf8_decode("Grade")   ,1, 0);
        $this->Cell(20,  5, utf8_decode("Quantidade")   ,1, 0);
        $this->Cell(110, 5, utf8_decode("Situação") ,1, 1);
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

    public function init($enderecos)
    {

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        foreach ($enderecos as $endereco) {

            $dscEndereco   = $endereco['descricao'];
            $idProduto     = $endereco['codProduto'];
            $grade         = $endereco['grade'];
            $qtd           = $endereco['qtd'];
            $uma           = $endereco['uma'];
            $idRecebimento = $endereco['idRecebimento'];
            $statusUma     = $endereco['sigla'];

            if ($endereco['codProduto'] == NULL) {
                $statusEndereco = "Endereço não utilizado";
                $tipo = "V";
            } else {
                if ($uma == NULL) {
                    $tipo = "E";
                    $statusEndereco = "Endereçado no estoque";
                } else {
                    $statusEndereco = "Reservado para o palete $uma ($statusUma) no recebimento $idRecebimento";
                    $tipo = "P";
                }
            }

            $this->Cell(20,5,$dscEndereco,1,0);
            if ($tipo == "V") {
                $this->Cell(175,5,utf8_decode($statusEndereco),1,1);
            } else {
                $this->Cell(20, 5, $idProduto,1, 0);
                $this->Cell(25, 5, utf8_decode($grade),1, 0);
                $this->Cell(20, 5, $qtd ,1, 0);
                $this->Cell(110, 5, utf8_decode($statusEndereco),1, 1);
            }

        }

        $this->Output('Disponibilidade-Estoque.pdf','D');
    }
}
