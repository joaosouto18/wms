<?php

namespace Wms\Module\Expedicao\Report;

use Wms\Util\Barcode\eFPDF,
    Wms\Util\CodigoBarras;

class EtiquetaEmbalados extends eFPDF
{

    public function imprimirExpedicaoModelo1($volumePatrimonio,$existeItensPendentes)
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);
        $this->SetAutoPageBreak(0,0);

        self::bodyExpedicaoModelo1($volumePatrimonio,$existeItensPendentes);

        $this->Output('Volume-Embalado.pdf','I');
        exit;
    }

    private function bodyExpedicaoModelo1($volume,$existeItensPendentes)
    {
        $this->SetFont('Arial', 'B', 20);
        //coloca o cod barras
        $this->AddPage();

        //monta o restante dos dados da etiqueta
        $this->SetFont('Arial', '', 10);
        $impressao = utf8_decode(substr($volume[0]['NOM_PESSOA']."\n",0,20));
        $this->MultiCell(110, 3.9, $impressao, 0, 'L');

        $this->SetFont('Arial', '', 10);
        $impressao = utf8_decode(substr("EXPEDIÇÃO: ".$volume[0]['COD_EXPEDICAO']."\n",0,50));
//        $this->MultiCell(110, 3.9, $impressao, 0, 'L');

        $this->SetFont('Arial', '', 10);
        $impressao = utf8_decode(substr('PLACA: '.$volume[0]['DSC_PLACA_CARGA']."\n",0,20));
        $this->MultiCell(110, 3.9, $impressao, 0, 'L');

        $this->SetFont('Arial', '', 10);
        $impressao = utf8_decode(substr('CARGA: '.$volume[0]['COD_CARGA_EXTERNO']."\n",0,20));
        $this->MultiCell(110, 3.9, $impressao, 0, 'L');

        $this->SetFont('Arial', '', 10);
        $impressao = utf8_decode(substr('ROTA: '.$volume[0]['DSC_ITINERARIO']."\n",0,20));
        $this->MultiCell(110, 3.9, $impressao, 0, 'L');

        $y = 12;
        $this->SetFont('Arial', 'B', 7);

        $impressao = 'VOLUME: '.$volume[0]['NUM_SEQUENCIA'];
        if ($existeItensPendentes == false)
            $impressao = 'VOLUME: '.$volume[0]['NUM_SEQUENCIA'].'/'.$volume[0]['NUM_SEQUENCIA'];

        $this->MultiCell(110, 3.9, $impressao, 0, 'L');
//        $this->SetXY(50,$y);
//        $this->Cell(55,$y+10, $impressao, 0, 'L');

        $this->Image(@CodigoBarras::gerarNovo($volume[0]['COD_MAPA_SEPARACAO_EMB_CLIENTE']) , 18, 22 , 35);
    }

}
