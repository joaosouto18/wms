<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao;
use Wms\Util\Barcode\Barcode;

use Wms\Util\Barcode\eFPDF;

class Carrefour extends eFPDF
{

    public function imprimir($params)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->AddPage();

        $this->SetFont('Arial','B',20);
        $this->Cell(180, 25, utf8_decode("Indústria Brasileira Ltda"),1,1,'C');
        $this->Cell(108, 25, utf8_decode("CONTENT/Conteúdo"),1,0,'C');
        $this->Cell(162, 25, utf8_decode("Net Weight(kg)/Peso Líquido"),1,1,'C');
        $this->Cell(162, 25, utf8_decode("SELL BY/Data de Validade"),1,0,'C');
        $this->Cell(108, 25, utf8_decode("COUNT/Quantidade"),1,1,'C');
        $this->Cell(108, 25, utf8_decode("BATCH/Lote"),1,0,'C');
        $this->Cell(162, 25, utf8_decode("PROD DATE/data de Prod."),1,1,'C');
        $this->Cell(150, 25, utf8_decode("Gross Weight (Kg)/Peso Bruto"),1,0,'C');
        $this->Cell(120, 25, utf8_decode("Nome do Produto"),1,1,'C');
        $this->Cell(270, 25, utf8_decode("PROCESSOR #/Nº Registro Processador"),1,1,'C');
        $this->Cell(270, 25, utf8_decode("SSCC - Código de Série da Unidade Logística"),1,1,'C');

        $this->Cell(270, 25, utf8_decode("Packaging Tare / Tara Embalagem"),1,1,'L');
        $this->Cell(270, 25, utf8_decode("Pallet Tare / Tara do Palete"),1,1,'L');
        $this->Cell(270, 25, utf8_decode("Rack Tare / Tara do Rack"),1,1,'L');
        $this->Cell(270, 25, utf8_decode("Strech Tare / Tara do Strech"),1,1,'L');
        $this->Cell(270, 25, utf8_decode("Corner Tare / Tara da Cantoneira"),1,1,'L');
        $this->Cell(270, 25, utf8_decode("Total Tare / Tara Total"),1,1,'L');

        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $x = 40;
        $y = 150;

        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>'(02)97898357410018(3102|072000(3302)080000)37|40'),0.1,10);
        $len = $this->GetStringWidth($data['hri']);
        $this->Text(($x-$height) + (($height - $len)/2) + 3, $y + 8,'(02)97898357410018(3102|072000(3302)080000)37|40');

        $x = 40;
        $y = 170;
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>'(15)160619(11)150619[7030]07612345(10)0001'),0.1,10);
        $len = $this->GetStringWidth($data['hri']);
        $this->Text(($x-$height) + (($height - $len)/2) + 3, $y + 8,'(15)160619(11)150619[7030]07612345(10)0001');

        $x = 40;
        $y = 190;
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>'(00)078983574100000015'),0.1,10);
        $len = $this->GetStringWidth($data['hri']);
        $this->Text(($x-$height) + (($height - $len)/2) + 3, $y + 8,'(00)078983574100000015');

        $this->Output('Carrefour.pdf','D');

    }

    public function Header()
    {
    }

    public function Footer()
    {
    }
}
