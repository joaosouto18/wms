<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class UMA extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE UMA's" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(26,  5, utf8_decode("Cód. Recebimento")  ,1, 0);
        $this->Cell(15,  5, utf8_decode("Cód. Uma")   ,1, 0);
        $this->Cell(20, 5, utf8_decode("Cód. Produto") ,1, 0);
        $this->Cell(66, 5, utf8_decode("Descrição Produto") ,1, 0);
        $this->Cell(18, 5, utf8_decode("Quantidade") ,1, 0);
        $this->Cell(37, 5, "Status" ,1, 0);
        $this->Cell(18,  5, utf8_decode("End. Uma") ,1, 1);
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


    public function init($params = array())
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $PaleteRepo */
        $PaleteRepo = $em->getRepository('wms:Enderecamento\Palete');

        $listaUMA = $PaleteRepo->getPaletesReport($params);

        foreach ($listaUMA as $uma) {

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(26, 5, $uma['codrecebimento'], 1, 0);
            $this->Cell(15, 5, $uma['coduma'], 1, 0);
            $this->Cell(20, 5, $uma['codproduto'], 1, 0);
            $this->Cell(66, 5, $uma['nomeproduto'], 1, 0);
            $this->Cell(18, 5, $uma['quantidade'], 1, 0);
            $this->Cell(37, 5, $uma['status'], 1, 0);
            $this->Cell(18, 5, $uma['endereco'], 1, 1);

        }

        $this->Output('UMA.pdf','D');
    }
}
