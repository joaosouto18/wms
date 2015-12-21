<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Domain\Entity\Expedicao;

class IdentificacaoCarga extends Pdf
{
    public function imprimir($idExpedicao, $codCargaExterno)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $cargaEn = $em->getRepository("wms:Expedicao\Carga")->findOneBy(array('codCargaExterno'=>$codCargaExterno,
                                                                                'codExpedicao'=>$idExpedicao));

        $expedicaoRepo = $em->getRepository("wms:Expedicao");
        $itinerarios = $expedicaoRepo->getItinerarios($idExpedicao,$cargaEn->getId());

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $arrayItinerario = array();
        foreach ($itinerarios as $itinerario) {
            $arrayItinerario[] = $itinerario['descricao'];
        }

        $this->AddPage();
        $this->SetFont('Arial',  "B", 60);
        $this->Cell(115, 22, utf8_decode("Expedição: "),0,0);
        $this->SetFont('Arial',  null, 60);
        $this->Cell(20,  22, utf8_decode($idExpedicao),0,1);
        $this->SetFont('Arial',  "B", 60);
        $this->Cell(65,  22, utf8_decode("Placa: "),0,0);
        $this->SetFont('Arial',  null, 60);
        $this->Cell(20,  22, utf8_decode($cargaEn->getPlacaCarga()),0,1);
        $this->SetFont('Arial',  "B", 60);
        $this->Cell(70,  22, utf8_decode("Carga: ") ,0,0);
        $this->SetFont('Arial',  null, 60);
        $this->Cell(20,  22, utf8_decode($codCargaExterno),0,1);

        $y = $this->GetY();
        $x = $this->GetX();

        $this->SetX(20);
        $this->SetY(50);
        $this->Cell(210,1,"",0,0);
        $this->SetFont('Arial',  null, 160);
        $this->Cell(1,1,$cargaEn->getSequencia(),0,0);

        $this->SetX($x);
        $this->SetY($y);

        //$this->Line(0,$this->GetY(),500,$this->GetY());
        $this->Cell(20,20,"",0,1);
        $this->SetFont('Arial',  null, 80);
        $this->MultiCell("280","25",utf8_decode(implode($arrayItinerario,",")),0,"C");


        $this->Output('IdentificaçãoCarga.pdf','D');

    }
}
