<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf;
use Doctrine\ORM\EntityManager;

class VolumeEmbalado extends Pdf
{

    private $expedicao;

    private function addHeader() {
        $this->AddPage();
        $this->SetMargins(7, 5, 0);

    }

    private function addFooter() {

    }

    private function addRow($item)
    {



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
        $this->Cell(62,5,utf8_decode('Itinerário'),0,0);
        $this->Cell(80,5,utf8_decode('Cliente'),0,1);
        $this->SetFont('Arial',null,11);
        foreach ($volums as $volume) {
            $this->Cell(18,5,utf8_decode($volume['VOLUME']),0,0);
            $this->Cell(30,5,utf8_decode($volume['DESCRICAO']),0,0);
            $this->Cell(62,5,substr( utf8_decode($volume['ITINERARIO']),0,26),0,0);
            $this->Cell(80,5,utf8_decode($volume['CLIENTE']),0,1);

        }

        $this->Cell(10,15,'',0,1);

        $this->Cell(20, 6.5, utf8_decode("Por ser expressão da verdade, dato, assino e dou fé."), 0,1);
        $this->Cell(195, 6.5, utf8_decode("Cidade:________________________________  Data: ____/____/______"), 0,1,'L');
        $this->Cell(195, 6.5, utf8_decode("Nome: _______________________________________"), 0,1,'L');
        $this->Cell(195, 6.5, utf8_decode("Assinatura: ____________________________________"), 0,1,'L');


    }

    private function prepare($itens) {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $rowLimit = 30;
        $i = 0;
        foreach ($itens as $key => $item) {
            if ($key == 0 or $i == $rowLimit) self::addHeader();
            self::addRow($item);
            $i++;
            if ($i == $rowLimit) self::addFooter();
        }

    }

    public function imprimir($idExpedicao)
    {
        $this->expedicao = $idExpedicao;

        /** @var EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $itens = $em->getRepository('wms:Expedicao')->getItensVolumeEmbalados($idExpedicao);

        $this->prepare($itens);

        $this->Output("Volumes_Embalados_Expedicao-$idExpedicao.pdf",'D');
    }


}
