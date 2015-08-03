<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf;

class VolumePatrimonio extends Pdf
{

    public function layout($volums)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 5, 0);
        $this->AddPage();

        $this->SetFont('Arial','B',15);
        $this->Cell(10,20,'',0,1);
        $this->Cell(195, 6.5, utf8_decode("DECLARAÇÃO"), 0,1,'C');
        $this->Cell(10,5,'',0,1);

        $this->SetFont('Arial',null,12);

        $this->Cell(20, 6.5, utf8_decode("Eu ___________________________________declaro para o devidos fins, ter recebido da"), 0,1);
        $this->Cell(20, 6.5, utf8_decode("Moveis Simonetti os volumes patrimônios (caixas) relacionados abaixo, devidamente lacrados:"), 0,1);
        $this->Cell(10,5,'',0,1);

        $this->SetFont('Arial','B',11);
        $this->Cell(18,5,utf8_decode('Volume'),0,0);
        $this->Cell(30,5,utf8_decode('Descrição'),0,0);
        $this->Cell(60,5,utf8_decode('Itinerário'),0,0);
        $this->Cell(80,5,utf8_decode('Cliente'),0,1);
        $this->SetFont('Arial',null,11);
        foreach ($volums as $volume) {
            $this->Cell(18,5,utf8_decode($volume['VOLUME']),0,0);
            $this->Cell(30,5,utf8_decode($volume['DESCRICAO']),0,0);
            $this->Cell(60,5,utf8_decode($volume['ITINERARIO']),0,0);
            $this->Cell(80,5,utf8_decode($volume['CLIENTE']),0,1);

        }

        $this->Cell(10,15,'',0,1);

        $this->Cell(20, 6.5, utf8_decode("Por ser expressão da verdade, dato, assino e dou fé."), 0,1);
        $this->Cell(195, 6.5, utf8_decode("Cidade:________________________________  Data: ____/____/______"), 0,1,'L');
        $this->Cell(195, 6.5, utf8_decode("Nome: _______________________________________"), 0,1,'L');
        $this->Cell(195, 6.5, utf8_decode("Assinatura: ____________________________________"), 0,1,'L');


    }

    public function imprimir($volumes)
    {
        $this->_em = \Zend_Registry::get('doctrine')->getEntityManager();
        $this->layout($volumes);

        $this->Output('declaracao.pdf','D');
    }


}
