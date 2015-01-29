<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class OcupacaoCD extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE ACOMPANHAMENTO DE OCUPAÇÃO CD" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30,  5, utf8_decode("Rua"),1, 0, "C");
        $this->Cell(45, 5, utf8_decode("Pos. Paletes Existentes") ,1, 0, "C");
        $this->Cell(45, 5, utf8_decode("Pos. Paletes Ocupados") ,1, 0,"C");
        $this->Cell(45, 5, utf8_decode("Pos. Paletes Disponiveis") ,1, 0,"C");
        $this->Cell(30, 5, utf8_decode("% Ocupação") ,1, 1, "C");
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

    public function imprimir($params)
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
          $produtos = $EnderecoRepo->getOcupacaoRuaReport($params);

        $total_existente=0;
        $total_ocupado=0;
        $total_disponivel=0;
        foreach ($produtos as $ocupacao) {

            $total_existente = $ocupacao['PALETES_EXISTENTES'] + $total_existente;
            $total_ocupado   = $ocupacao['PALETES_OCUPADOS']   + $total_ocupado;
            $total_disponivel = ($ocupacao['PALETES_EXISTENTES']- $ocupacao['PALETES_OCUPADOS']) + $total_disponivel;

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(30, 5, $ocupacao['RUA'] ,0, 0, "C");
            $this->Cell(45, 5, $ocupacao['PALETES_EXISTENTES'] ,0, 0, "C");
            $this->Cell(45, 5, $ocupacao['PALETES_OCUPADOS'] ,0, 0, "C");
            $this->Cell(45, 5, $ocupacao['PALETES_EXISTENTES']- $ocupacao['PALETES_OCUPADOS'] ,0, 0, "C");
            $this->Cell(30, 5, $ocupacao['PERCENTUAL_OCUPADOS'] . " %" ,0, 1, "C");
        }

        $total_percentual = ($total_ocupado * 100) / $total_existente;

        $this->Ln();
        $this->Cell(30, 5, '' ,0, 0);
        $this->Cell(45, 5, $total_existente ,0, 0, "C");
        $this->Cell(45, 5, $total_ocupado ,0, 0, "C");
        $this->Cell(45, 5, $total_disponivel ,0, 0, "C");
        $this->Cell(30, 5, number_format($total_percentual, 2, '.', ',') . " %" ,0, 1, "C");

        $this->Output('OcupacaoCD.pdf','D');
    }
}
