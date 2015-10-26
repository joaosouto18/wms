<?php

namespace Wms\Module\Expedicao\Report;

use Wms\Controller\Action,
    Wms\Util\Barcode\eFPDF,
    Core\Pdf,
    Wms\Util\Barcode\Barcode,
    Wms\Util\CodigoBarras;

/**
 * Description of GerarEtiqueta
 *
 * @author adriano uliana
 * modificado por Lucas Chinelate
 */
class EtiquetaVolume extends eFPDF
{

    public function init($etiquetas = array())
    {

        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        $this->SetMargins(7, 5, 0);
        $this->SetFont('Arial', 'B', 8);

        foreach ($etiquetas as $etiqueta) {
            $this->layoutEtiqueta($etiqueta['id'],$etiqueta['descricao']);
        }
        $this->Output("Etiqueta.pdf","D");
        exit;
    }

    /**
     * @param $produto
     * @param $tipo
     */
    public function layoutEtiqueta($codigo, $descricao)
    {
        $this->SetFont('Arial', 'B', 20);

        $this->AddPage();
        $dsc = utf8_decode($codigo) .' - '.utf8_decode($descricao);
        $lentxt = $this->GetStringWidth($dsc);

        $height   = 8;
        $angle    = 0;
        $x        = 55;
        $y        = 14;
        $x2 = ($x-$height) + (($height - $lentxt)/2) + 3;
        $y2 = 30;

        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),1,18);

        $this->Text($x2,$y2 ,$dsc, 0, 0);

    }

    public function imprimirExpedicaoModelo1($volumePatrimonio)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);
        $this->SetAutoPageBreak(0,0);
        foreach ($volumePatrimonio as $volume) {
            $this->SetFont('Arial', 'B', 20);
            //coloca o cod barras
            $this->AddPage();

            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 12.5);
//            $impressao = utf8_decode("EXP: $volume[expedicao] CLI: $volume[quebra]\n");
//            $volume['quebra'] = "TOMAZ GOMIDE NUNES - PREÇO REVENDA";
            $impressao = utf8_decode(substr("CLI: $volume[quebra]\n",0,50));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 13);
            $impressao = utf8_decode("Pedido:");
            $this->SetY(15);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 30);
            $volume['pedido'] = 2611;
            $impressao = utf8_decode("\n$volume[pedido]");
            $this->SetY(17);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 7);
            $impressao = utf8_decode("Código                          Produto                                                    Qtd.\n");
            $this->SetX(5);
            $this->SetY(10);
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');

            //linha horizontal entre codigo produto quantidade e a descricao dos dados
            $this->Line(0,14,150,14);
            //linha vertical entre o codigo e a descrição do produto
            $this->Line(19,14,19,100);
            //linha vertical entre a descrição do produto e a quantidade
            $this->Line(73,14,73,100);
            //linha vertical entre a quantidade e o numero do pedido
            $this->Line(82,14,82,100);
            //linha horizontal entre o numero do pedido e o cod de barras
            $this->Line(82,30,150,30);

            $y = 15;
            $this->SetFont('Arial', 'B', 7);

            foreach ($volume['produtos'] as $produtos) {

                $impressao = utf8_decode($produtos['codProduto']);
                $this->SetX(3);
                $this->SetY($y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = utf8_decode(substr($produtos['descricao'], 0, 33));
                $this->SetXY(19,$y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = $produtos['quantidade'];
                $this->SetXY(75,$y);
                $this->Cell(75,$y, $impressao, 0, 'L');

                $y = $y + 2;
            }

            $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 87, 35, 20,5);

            $dsc = utf8_decode($volume['volume']) .' - '.utf8_decode($volume['descricao']);
            $lentxt = $this->GetStringWidth($dsc);

            $angle    = 0;
            $x        = 96;
            $y        = 46;

            $type     = 'code128';
            $black    = '000000';
            $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$volume['volume']),0.35,6);
        }
        $this->Output('Volume-Patrimonio.pdf','I');
    }

    public function imprimirExpedicaoModelo2($volumePatrimonio)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);
        $this->SetAutoPageBreak(0,0);
        foreach ($volumePatrimonio as $volume) {
            $this->SetFont('Arial', 'B', 20);
            //coloca o cod barras
            $this->AddPage();

            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 8);
            $impressao = utf8_decode("EXP: $volume[expedicao] CLIENTE: $volume[quebra]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');


            $this->SetFont('Arial', 'B', 10);
            $impressao = utf8_decode("Pedido:");
            $this->SetY(15);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 15);
            $impressao = utf8_decode("\n$volume[pedido]");
            $this->SetY(15);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 8);
            $impressao = utf8_decode("Código                     Produto                                           Qtd.\n");
            $this->SetX(5);
            $this->SetY(6);
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');

            //linha horizontal entre codigo produto quantidade e a descricao dos dados
            $this->Line(0,10,150,10);
            //linha vertical entre o codigo e a descrição do produto
            $this->Line(19,10,19,100);
            //linha vertical entre a descrição do produto e a quantidade
            $this->Line(73,10,73,100);
            //linha vertical entre a quantidade e o numero do pedido
            $this->Line(82,10,82,100);
            //linha horizontal entre o numero do pedido e o cod de barras
            $this->Line(82,30,150,30);

            $y = 8;
            $this->SetFont('Arial', 'B', 7);

            foreach ($volume['produtos'] as $produtos) {

                $impressao = utf8_decode($produtos['codProduto']);
                $this->SetX(3);
                $this->SetY($y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = utf8_decode(substr($produtos['descricao'], 0, 33));
                $this->SetXY(19,$y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = $produtos['quantidade'];
                $this->SetXY(75,$y);
                $this->Cell(75,$y, $impressao, 0, 'L');

                $y = $y + 2;
            }

            $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 87, 1.5, 20,5);

            $dsc = utf8_decode($volume['volume']) .' - '.utf8_decode($volume['descricao']);
            $lentxt = $this->GetStringWidth($dsc);

            $angle    = 0;
            $x        = 96;
            $y        = 58;

            $type     = 'code128';
            $black    = '000000';
            $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$volume['volume']),0.35,6);
        }
        $this->Output('Volume-Patrimonio.pdf','I');

    }

}
