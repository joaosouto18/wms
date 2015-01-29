<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class OcupacaoCDPeriodo extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE ACOMPANHAMENTO DE OCUPAÇÃO CD POR PERÍODO" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(25,  5, utf8_decode("Data"),1, 0, "C");
        $this->Cell(25,  5, utf8_decode("Rua"),1, 0, "C");
        $this->Cell(40, 5, utf8_decode("Pos. Existentes") ,1, 0,"C");
        $this->Cell(40, 5, utf8_decode("Pos. Ocupados") ,1, 0,"C");
        $this->Cell(40, 5, utf8_decode("Pos. Disponiveis") ,1, 0,"C");
        $this->Cell(25, 5, utf8_decode("% Ocupação") ,1, 1, "C");
    }

    public function layout()
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 5, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

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

    public function init($params)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EnderecoRepo */
        $EnderecoRepo = $em->getRepository('wms:Deposito\Endereco');

        $produtos = $EnderecoRepo->getOcupacaoPeriodoResumidoReport($params);

        if (count($produtos) > 0) {
            $dataAnterior = $produtos[0]['DTH_ESTOQUE'];
        }
        $this->SetFont('Arial', 'B', 8);

        $totalEnderecos = 0;
        $totalOcupados = 0;
        $totalVazios = 0;
        foreach ($produtos as $ocupacao) {

            if ($dataAnterior != $ocupacao['DTH_ESTOQUE']) {
                //$this->Ln();
                $ocupacaoTotal = ($totalOcupados * 100)/$totalEnderecos;

                $this->Cell(25, 5, $dataAnterior ,0, 0, "C");
                $this->Cell(25, 5, 'TOTAL' ,0, 0, "C");
                $this->Cell(40, 5, $totalEnderecos ,0, 0, "C");
                $this->Cell(40, 5, $totalOcupados ,0, 0, "C");
                $this->Cell(40, 5, $totalVazios ,0, 0, "C");
                $this->Cell(25, 5, number_format($ocupacaoTotal, 2, '.', ',') . " %" ,0, 1, "C");

                $totalEnderecos = 0;
                $totalOcupados = 0;
                $totalVazios = 0;

                $dataAnterior = $ocupacao['DTH_ESTOQUE'];
                    $this->Ln();
                    $this->Line(10,$this->GetY(), 200,$this->GetY());
                    $this->Ln();
            }

            $this->Cell(25, 5, $ocupacao['DTH_ESTOQUE'] ,0, 0, "C");
            $this->Cell(25, 5, $ocupacao['NUM_RUA'] ,0, 0, "C");
            $this->Cell(40, 5, $ocupacao['QTD_EXISTENTES'] ,0, 0, "C");
            $this->Cell(40, 5, $ocupacao['QTD_OCUPADOS'] ,0, 0, "C");
            $this->Cell(40, 5, $ocupacao['QTD_VAZIOS'] ,0, 0, "C");
            $this->Cell(25, 5, $ocupacao['OCUPACAO'] . " %" ,0, 1, "C");

            $totalEnderecos = $totalEnderecos + $ocupacao['QTD_EXISTENTES'];
            $totalOcupados = $totalOcupados + $ocupacao['QTD_OCUPADOS'];
            $totalVazios = $totalVazios +  $ocupacao['QTD_VAZIOS'];
        }

        if (count($produtos) > 0) {
            $ocupacaoTotal = ($totalOcupados * 100)/$totalEnderecos;
            $this->Cell(25, 5, $dataAnterior ,0, 0, "C");
            $this->Cell(25, 5, 'TOTAL' ,0, 0, "C");
            $this->Cell(40, 5, $totalEnderecos ,0, 0, "C");
            $this->Cell(40, 5, $totalOcupados ,0, 0, "C");
            $this->Cell(40, 5, $totalVazios ,0, 0, "C");
            $this->Cell(25, 5, number_format($ocupacaoTotal, 2, '.', ',') . " %" ,0, 1, "C");
        }

        $this->Output('OcupacaoCDPeriodo.pdf','D');
    }
}
