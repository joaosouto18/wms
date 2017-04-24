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
        $this->SetFont('Arial','B',10);
        $this->Cell(24, 5, utf8_decode("PESSOA: "), 1, 0);
        $this->Cell(20, 5, utf8_decode("EXPED.: "), 1, 0);
        $this->Cell(24, 5, utf8_decode("MAPA SEP.: "), 1, 0);
        $this->Cell(18, 5, utf8_decode("PESO: "), 1, 0);
        $this->Cell(15, 5, utf8_decode("VOL.: "), 1, 0);
        $this->Cell(15, 5, utf8_decode("QTD.: "), 1, 0);
        $this->Cell(40, 5, utf8_decode("DTH INICIO: "), 1, 0);
        $this->Cell(40, 5, utf8_decode("DTH FIM: "), 1, 0);
        $this->Cell(70, 5, utf8_decode("TEMPO: "), 1, 1);
        foreach ($produtividade as $item) {
            $this->SetFont('Arial','',10);
            $this->Cell(24, 4, substr(utf8_decode($item['NOM_PESSOA']),0,10), 1, 0);
            $this->Cell(20, 4, utf8_decode($item['COD_EXPEDICAO']), 1, 0);
            $this->Cell(24, 4, utf8_decode($item['COD_MAPA_SEPARACAO']), 1, 0);
            $this->Cell(18, 4, utf8_decode($item['NUM_PESO']), 1, 0);
            $this->Cell(15, 4, utf8_decode($item['VOLUMES']), 1, 0);
            $this->Cell(15, 4, utf8_decode($item['QTD_PRODUTOS']), 1, 0);
            $this->Cell(40, 4, utf8_decode($item['DTH_INICIO']), 1, 0);
            $this->Cell(40, 4, utf8_decode($item['DTH_FIM']), 1, 0);
            $this->Cell(70, 4, utf8_decode($item['TEMPO_GASTO']), 1, 1);

        }
        $this->Output('Relatório de Produtividade.pdf','D');

    }

}
