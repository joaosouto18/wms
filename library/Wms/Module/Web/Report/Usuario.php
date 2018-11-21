<?php

namespace Wms\Module\Web\Report;

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
class Usuario extends eFPDF
{

    public function init($codigo, $usuario)
    {

        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        $this->SetMargins(0, 5, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->layoutEtiqueta($codigo,$usuario);
        $this->Output("Usuario-$usuario.pdf","D");
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
        $dsc = utf8_decode($descricao);
        $lentxt = $this->GetStringWidth($dsc);

        $height   = 8;
        $angle    = 0;
        $x        = 55;
        $y        = 20;
        $x2 = ($x-$height) + (($height - $lentxt)/2) + 3;
        $y2 = 40;

        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.6,25);
        $this->Text($x2,$y2 ,$dsc, 0, 0);

    }

}
