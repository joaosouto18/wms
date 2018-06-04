<?php
namespace Wms\Module\Expedicao\Report;

use Core\Pdf;

class ProdutosConferidos extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE VOLUMES PATRIMÔNIOS USADOS NA EXPEDIÇÃO"), 0, 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30, 5, utf8_decode("Cód Barras") ,0, 0);
        $this->Cell(70, 5, utf8_decode("Cliente") ,0, 0);
        $this->Cell(50, 5, utf8_decode("Cód Produto") ,0, 0);
        $this->Cell(75, 5, utf8_decode("Produto") ,0, 0);
        $this->Cell(30, 5, utf8_decode("Cód. Carga") ,0, 0);
        $this->Cell(45, 5, utf8_decode("Grade") ,0, 1);

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


        foreach($params as $result)
        {
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(30, 5, $result['codBarras'], 0, 0);
            $this->Cell(70, 5, utf8_decode($result['cliente']), 0, 0);
            $this->Cell(50, 5, $result['codProduto'],0, 0);
            $this->Cell(75, 5, utf8_decode($result['produto']), 0, 0);
            $this->Cell(30, 5, $result['codCargaExterno'], 0, 0);
            $this->Cell(45, 5, $result['grade'],0, 1);
            $this->Cell(30, 5, utf8_decode("Cód. Estoque:  ") . $result['codEstoque'] ,0, 0);
            $this->Cell(70, 5, utf8_decode("Embalagem: ") . $result['embalagem'] ,0, 0);
            $this->Cell(50, 5, utf8_decode("Data Conferência:  ") . $result['dataConferencia']->format('d/m/y H:i:s') ,0, 0);
            $this->Cell(75, 5, utf8_decode("Conferente:  ") . $result['conferente'] ,0, 0);
            $this->Cell(40, 5, utf8_decode("Volume Patrimônio") . $result['volumePatrimonio'], 0, 1);
            $this->Line(8,$this->GetY(), 290,$this->GetY());
            $this->Ln();
        }

        $this->Output('produtos-conferidos.pdf','D');
    }
}
