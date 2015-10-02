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
        $this->Cell(30,  5, utf8_decode("Rua"),1, 0, "C");
        $this->Cell(50, 5, utf8_decode("Pos. Paletes Existentes") ,1, 0);
        $this->Cell(50, 5, utf8_decode("Pos. Paletes Ocupados") ,1, 0);
        $this->Cell(40, 5, utf8_decode("% Ocupação") ,1, 1, "C");
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

        $produtos = $EnderecoRepo->getOcupacaoPeriodoReport($params);

        $total_existente=0;
        $total_ocupado=0;
        $totalExistenteGeral = 0;
        $totalOcupadoGeral = 0;
        $totalPercentualGeral = 0;
        if (count($produtos) > 0) {
            $dataAnterior = $produtos[0]['DATA_ESTOQUE'];
        }
        $this->SetFont('Arial', 'B', 8);

        if (count($produtos) > 0) {
            foreach ($produtos as $ocupacao) {

                if ($dataAnterior != $ocupacao['DATA_ESTOQUE']) {
                    //$this->Ln();
                    $dataAnterior = $ocupacao['DATA_ESTOQUE'];

                    $total_percentual = ($total_ocupado * 100) / $total_existente;

                    $this->Cell(55, 5, '' ,0, 0);
                    $this->Cell(50, 5, $total_existente ,0, 0, "C");
                    $this->Cell(50, 5, $total_ocupado ,0, 0, "C");
                    $this->Cell(40, 5, number_format($total_percentual, 2, '.', ',') . " %" ,0, 1, "C");
                    $this->Ln();
                    $this->Line(10,$this->GetY(), 200,$this->GetY());
                    $this->Ln();

                    $total_existente=0;
                    $total_ocupado=0;

                }
                $total_existente = $ocupacao['PALETES_EXISTENTES'] + $total_existente;
                $total_ocupado = $ocupacao['PALETES_OCUPADOS'] + $total_ocupado;

                $this->Cell(25, 5, $ocupacao['DATA_ESTOQUE'] ,0, 0, "C");
                $this->Cell(30, 5, $ocupacao['RUA'] ,0, 0, "C");
                $this->Cell(50, 5, $ocupacao['PALETES_EXISTENTES'] ,0, 0, "C");
                $this->Cell(50, 5, $ocupacao['PALETES_OCUPADOS'] ,0, 0, "C");
                $this->Cell(40, 5, number_format($ocupacao['PERCENTUAL_OCUPADOS'],2,'.',',') . " %" ,0, 1, "C");
            }

        }

//        $this->Ln();
//        $this->Cell(55, 5, 'Totais Gerais' ,0, 0);
//        $this->Cell(50, 5, $totalExistenteGeral ,0, 0, "C");
//        $this->Cell(50, 5, $totalOcupadoGeral ,0, 0, "C");
//        $this->Cell(40, 5, number_format($totalPercentualGeral, 2, '.', ',') . " %" ,0, 1, "C");

        $this->Output('OcupacaoCDPeriodo.pdf','D');
    }
}
