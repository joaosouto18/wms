<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Domain\Entity\Expedicao;

class ProdutosCarregamento extends Pdf
{
    public function imprimir($query)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->AddPage();


        $this->SetFont('Arial',  "B", 12);

        $clienteAnterior = null;
        foreach ($query as $valor) {

            if($clienteAnterior == $valor['CLIENTE']) continue;

            $this->Cell(10, 10, utf8_decode("Cliente: $valor[CLIENTE]"),0,1);

            $clienteAnterior = $valor['CLIENTE'];

        }


//        $this->Cell(10, 10, utf8_decode("Cliente: "),0,0);
//        $this->SetFont('Arial',  null, 60);
//        $this->Cell(20,  22, utf8_decode($idExpedicao),0,1);
//        $this->SetFont('Arial',  "B", 60);
//        $this->Cell(65,  22, utf8_decode("Placa: "),0,0);
//        $this->SetFont('Arial',  null, 60);
//        $this->Cell(20,  22, utf8_decode($cargaEn->getPlacaCarga()),0,1);
//        $this->SetFont('Arial',  "B", 60);
//        $this->Cell(70,  22, utf8_decode("Carga: ") ,0,0);
//        $this->SetFont('Arial',  null, 60);
//        $this->Cell(20,  22, utf8_decode($codCargaExterno),0,1);

        $y = $this->GetY();
        $x = $this->GetX();

//        $this->SetX(20);
//        $this->SetY(50);
//        $this->Cell(210,1,"",0,0);
//        $this->SetFont('Arial',  null, 160);
//        $this->Cell(1,1,$cargaEn->getSequencia(),0,0);

        $this->SetX($x);
        $this->SetY($y);

        //$this->Line(0,$this->GetY(),500,$this->GetY());
//        $this->Cell(20,20,"",0,1);
//        $this->SetFont('Arial',  null, 80);
//        $this->MultiCell("280","25",utf8_decode(implode($arrayItinerario,",")),0,"C");


        $this->Output('IdentificaçãoCarga.pdf','D');

    }

    /*
    private function produtosParaCliente($produto, $i)
    {
        $lineH = $this->lineH;

        // LINHA 1

        $this->SetFont('Arial', 'B', 10);
        $this->SetY($this->prodListY + ($i * (5 + (3 * $lineH))));
        $this->SetFillColor(170);
        $this->Cell(40, $lineH, utf8_decode("Produto: $produto[COD_PRODUTO]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 126;
        $str = self::setStringByMaxWidth(utf8_decode("Descrição: $produto[DESCRICAO]"),$cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(190);
        $this->Cell(30, $lineH, utf8_decode("Grade: $produto[GRADE]") ,1 ,1 ,'' , true);

        // LINHA 2

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 106;
        $str = self::setStringByMaxWidth(utf8_decode("Linha de separação: $produto[LINHA_SEPARACAO]"), $cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $this->Cell(40, $lineH, utf8_decode("Endereço: $produto[ENDERECO]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $strg = (!empty($produto['VALIDADE']))? $produto['VALIDADE'] : 'Sem Registro';
        $this->Cell(50, $lineH, utf8_decode("Data de validade: $strg") ,1 ,1 ,'' , true);

        //LINHA 3

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $cellWidth = 120;
        $str = self::setStringByMaxWidth(utf8_decode("Fornecedor: $produto[FORNECEDOR]"), $cellWidth);
        $this->Cell($cellWidth, $lineH, $str ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220);
        $this->Cell(40, $lineH, utf8_decode("Qtd em estoque: $produto[QTD]") ,1 ,0 ,'' , true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(175);

        $dt = date_create_from_format('d/m/Y', $produto['VALIDADE']) ;
        $now = date_create_from_format('d/m/Y', date('d/m/Y'));
        if (!empty($produto['VALIDADE']) && ($dt <= $now)){
            $status = "VENCIDO";
        } else if (!empty($produto['VALIDADE']) && ($dt > $now)) {
            $status = "À VENCER";
        } else {
            $status = 'N/D';
        }

        $this->Cell(36, $lineH, utf8_decode("STATUS: $status") ,1 ,0 ,'C' , true);
    }
    */
}
