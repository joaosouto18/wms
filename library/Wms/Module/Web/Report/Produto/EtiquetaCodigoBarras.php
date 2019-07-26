<?php

namespace Wms\Module\Web\Report\Produto;


use Wms\Util\Barcode\eFPDF,
    Wms\Util\Barcode\Barcode;
use Wms\Util\CodigoBarras;

class EtiquetaCodigoBarras extends eFPDF
{

    public function init($idExpedicao)
    {
        $this->idExpedicao = $idExpedicao;
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $produtos = $mapaSeparacaoProdutoRepo->getMapaProdutoByExpedicao($idExpedicao);

        $x = 175;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';

        $startPage = function ($idMapa, $quebrasEtiqueta) {
            $this->AddPage();
            $y = 50;
            $count = 1;
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(20, 4, utf8_decode("MAPA: "), 0, 0);
            $this->SetFont('Arial', null, 10);
            $this->Cell(20, 4, utf8_decode($idMapa), 0, 1);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial', null, 10);
            $this->Cell(20, 4, utf8_decode($quebrasEtiqueta), 0, 1);

            $imgCodBarras = @CodigoBarras::gerarNovo($idMapa);
            $this->Image($imgCodBarras, 150, 12, 50);

            $this->SetFont('Arial', 'B', 10);
            $this->Cell(20, 20, "CODIGO", 0, 0);
            $this->Cell(20, 20, "GRADE", 0, 0);
            $this->Cell(80, 20, "PRODUTO", 0, 0);
            $this->Cell(15, 20, "UNID.MEDIDA", 0, 1);
            return [$y, $count];
        };

        $lastMapa = null;
        $count = 0;

        foreach ($produtos as $produto)
        {
            if($lastMapa != $produto['codMapa'] || $count > 11){
                list($y, $count) = $startPage($produto['codMapa'], $produto['dscQuebra']);
                $lastMapa = $produto['codMapa'];
            }

            $this->SetFont('Arial','',10);
            $this->Cell(20, 20, $produto['id'], 0, 0);
            $this->Cell(20, 20, $this->SetStringByMaxWidth($produto['grade'],20), 0, 0);
            $this->Cell(80, 20, $this->SetStringByMaxWidth($produto['descricao'],80), 0, 0);
            $this->Cell(15, 20, $produto['unidadeMedida'], 0, 1, 'C');

            $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$produto['codigoBarras']),0.5,10);
            $len = $this->GetStringWidth($data['hri']);
            $this->Text(($x-$height) + (($height - $len)/2) + 3, $y + 8,$produto['codigoBarras']);
            $y = $y + 20;
            $count++;
        }

        $this->Output('Código de Barras Expedicao '.$idExpedicao.'.pdf','D');
    }

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 10, utf8_decode("RELATÓRIO DE CODIGO DE BARRAS DE PRODUTOS DA EXPEDIÇÃO ". $this->idExpedicao), 0, 1);

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


}
