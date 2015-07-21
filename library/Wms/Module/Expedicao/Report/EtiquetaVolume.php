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
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),1.5,18);

        $this->Text($x2,$y2 ,$dsc, 0, 0);

    }

    public function imprimirExpedicao($volumePatrimonio)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);

        foreach ($volumePatrimonio as $volume) {
            $this->SetFont('Arial', 'B', 20);
            //coloca o cod barras
            $this->AddPage();
            $dsc = utf8_decode($volume['volume']) .' - '.utf8_decode($volume['descricao']);
            $lentxt = $this->GetStringWidth($dsc);

            $height   = 8;
            $angle    = 0;
            $x        = 50;
            $y        = 35;
            $x2 = ($x-$height) + (($height - $lentxt)/2) + 3;
            $y2 = 30;

            $type     = 'code128';
            $black    = '000000';
            $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$volume['volume']),0.75,10);

//monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 11);
            $impressao = utf8_decode("\n\nEXP:$volume[expedicao]. CLIENTE:$volume[quebra]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');
			
            $this->SetFont('Arial', 'B', 20);
            $impressao = utf8_decode("Pedido: $volume[pedido]");
            $this->MultiCell(100, 6, $impressao, 0, 'L');
			
			$this->SetFont('Arial', 'B', 15);
            $impressao = "PRODUTOS DIVERSOS";
            $this->MultiCell(100, 6, $impressao, 0, 'C');

            $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 4.1, 1.5, 20,5);
        }
        $this->Output('Volume-Patrimonio.pdf','I');

    }

}
