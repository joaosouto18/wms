<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Util\Barcode\Barcode;

use Wms\Util\Barcode\eFPDF;

class Carregamento extends eFPDF
{

    public function imprimir($codExpedicao,$dados)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->AddPage('L');

        //EXPEDICAO
        $this->SetFont('Arial', "B", 14);
        $this->Cell(30, 6, utf8_decode('Expedição: '),0,0);
        $this->SetFont('Arial', '', 14);
        $this->Cell(50, 6, $codExpedicao,0,0);

        //CARGA
        $this->SetFont('Arial', "B", 14);
        $this->Cell(22, 6, utf8_decode('Cargas: '),0,0);
        $this->SetFont('Arial', '', 14);
        $this->Cell(50, 6, utf8_decode($dados[0]['carga']),0,1);

        //PLACA
        $this->SetFont('Arial', "B", 14);
        $this->Cell(30, 6, utf8_decode('Placa: '),0,0);
        $this->SetFont('Arial', '', 14);
        $this->Cell(50, 6, utf8_decode($dados[0]['placa']),0,0);

        //ITINERÁRIO
        $this->SetFont('Arial', "B", 14);
        $this->Cell(22, 6, utf8_decode('Rota: '),0,0);
        $this->SetFont('Arial', '', 14);
        $this->Cell(50, 6, utf8_decode($dados[0]['itinerario']),0,1);


        //CABEÇALHO
        $this->SetFont('Arial',  "B", 12);
        $this->Cell(20, 10, utf8_decode("Seq.:"),1,0);
        $this->Cell(20, 10, utf8_decode("Pedido:"),1,0);
        $this->Cell(40, 10, utf8_decode("Cidade:"),1,0);
        $this->Cell(40, 10, utf8_decode("Bairro:"),1,0);
        $this->Cell(70, 10, utf8_decode("Rua:"),1,0);
        $this->Cell(95, 10, utf8_decode("Cliente:"),1,1);

        //DADOS
        foreach ($dados as $item) {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 10, utf8_decode($item['sequencia']),1,0);
            $this->Cell(20, 10, utf8_decode($item['pedido']),1,0);
            $this->Cell(40, 10, utf8_decode($item['cidade']),1,0);
            $this->Cell(40, 10, utf8_decode(substr($item['bairro'],0,17)),1,0);
            $this->Cell(70, 10, utf8_decode($item['rua']),1,0);
            $this->Cell(95, 10, utf8_decode(substr($item['cliente'],0,44)),1,1);
        }

        $this->Output('Carregamento-'.'8242'.'.pdf','D');
    }






}
