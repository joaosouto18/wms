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
        $this->Cell(16,  5, utf8_decode("Receb.")  ,1, 0);
        $this->Cell(15,  5, utf8_decode("U.M.A.")   ,1, 0);
        $this->Cell(16, 5, utf8_decode("Prod.") ,1, 0);
        $this->Cell(66, 5, utf8_decode("Descrição Produto") ,1, 0);
        $this->Cell(110, 5, utf8_decode("Embalagem/Volume") ,1, 0);
        $this->Cell(12, 5, utf8_decode("Qtde.") ,1, 0);
        $this->Cell(30, 5, "Status" ,1, 0);
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

        $listaUMA = $PaleteRepo->getPaletesAndVolumes($params['idRecebimento'],null,null,null,$params['status'],$params['dataInicial1'],$params['dataInicial2'], $params['dataFinal1'], $params['dataFinal2'],$params['uma']);

        foreach ($listaUMA as $uma) {

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(16, 5, $uma['COD_RECEBIMENTO'], 1, 0);
            $this->Cell(15, 5, $uma['UMA'], 1, 0);
            $this->Cell(16, 5, $uma['COD_PRODUTO'], 1, 0);
            $this->Cell(66, 5, substr($uma['DSC_PRODUTO'],0,30), 1, 0);
            if (strlen($uma['VOLUMES']) >= 70) {
                $this->Cell(110, 5, substr($uma['VOLUMES'],0,63) . "...", 1, 0);
            } else {
                $this->Cell(110, 5, $uma['VOLUMES'], 1, 0);
            }
            $this->Cell(12, 5, $uma['QTD'], 1, 0);
            $this->Cell(30, 5, $uma['STATUS'], 1, 0);
            $this->Cell(18, 5, $uma['ENDERECO'], 1, 1);

        }

        $this->Output('UMA.pdf','D');
    }
}
