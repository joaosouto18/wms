<?php

namespace Wms\Module\Produtividade\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Util\Barcode\Barcode;

use Wms\Util\Barcode\eFPDF;

class ProdutividadeDetalhada extends eFPDF
{

    public function imprimir($produtividade)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->AddPage();
        $this->SetFont('Arial','B',9);
        $this->Cell(30, 5, utf8_decode("PESSOA "), 1, 0);
        $this->Cell(35, 5, utf8_decode("ATIVIDADE "), 1, 0);
        $this->Cell(20, 5, utf8_decode("COD. "), 1, 0);
//        $this->Cell(20, 5, utf8_decode("EXPED.: "), 1, 0);
//        $this->Cell(24, 5, utf8_decode("MAPA SEP.: "), 1, 0);
        $this->Cell(20, 5, utf8_decode("PESO "), 1, 0);
        $this->Cell(20, 5, utf8_decode("VOL. "), 1, 0);
        $this->Cell(20, 5, utf8_decode("CUB. "), 1, 0);
        $this->Cell(15, 5, utf8_decode("PRO. "), 1, 0);
        $this->Cell(20, 5, utf8_decode("CARGA "), 1, 0);
        $this->Cell(15, 5, utf8_decode("PALETE "), 1, 0);
        $this->Cell(33, 5, utf8_decode("DTH INICIO "), 1, 0);
        $this->Cell(33, 5, utf8_decode("DTH FIM "), 1, 0);
        $this->Cell(17, 5, utf8_decode("TEMPO "), 1, 1);
        foreach ($produtividade as $item) {
            $this->SetFont('Arial','',8);
            $this->Cell(30, 4, substr(utf8_decode($item['NOM_PESSOA']),0,15), 1, 0);
            $this->Cell(35, 4, substr(utf8_decode($item['DSC_ATIVIDADE']),0,20), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['IDENTIDADE']), 1, 0);
//            $this->Cell(20, 4, utf8_decode($item['COD_EXPEDICAO']), 1, 0);
//            $this->Cell(24, 4, utf8_decode($item['COD_MAPA_SEPARACAO']), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['QTD_PESO']), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['QTD_VOLUMES']), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['QTD_CUBAGEM']), 1, 0);
            $this->Cell(15, 4, utf8_decode($item['QTD_PRODUTOS']), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['QTD_CARGA']), 1, 0);
            $this->Cell(15, 4, utf8_decode($item['QTD_PALETES']), 1, 0);
            $this->Cell(33, 4, utf8_decode($item['DTH_INICIO']), 1, 0);
            $this->Cell(33, 4, utf8_decode($item['DTH_FIM']), 1, 0);
            $this->Cell(17, 4, utf8_decode($item['TEMPO_GASTO']), 1, 1);

        }
        $this->Output('Relatório de Produtividade.pdf','D');

    }

}
