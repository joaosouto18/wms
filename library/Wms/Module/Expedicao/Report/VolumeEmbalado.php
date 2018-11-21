<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf;
use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbalado;

class VolumeEmbalado extends Pdf
{

    private $wPage;
    private $expedicao;

    private $rowLimit = 32;

    private $wN          =  8;
    private $wColCliente = 60;
    private $wColVolume  = 12;
    private $wColStatus  = 35;
    private $wColCodProd = 27;
    private $wColDesc    = 88;
    private $wColGrade   = 24;
    private $wColQtd     = 12;
    private $wColEmb     = 20;

    private $rowHeight = 5;

    private $arrStatusVolumes = [
        MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO => "CONF. INICIADA",
        MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO => "CONF. FINALIZADA",
        MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO => "VOL. FECHADO"
    ];

    private function addHeader() {
        $this->AddPage();

        $this->SetFont('Arial','B',15);
        $this->Cell($this->wPage, 10, utf8_decode("Relatório de volumes embalados da expedição $this->expedicao"), 0,1,'C');
        $this->Cell(10,5,'',0,1);

        $this->SetFont('Arial','B',11);
        $this->Cell($this->wN          ,6, utf8_decode('Nº'),1,0,'C');
        $this->Cell($this->wColCliente ,6, utf8_decode('Cliente'),1,0);
        $this->Cell($this->wColVolume  ,6, utf8_decode('Vol.'),1,0);
        $this->Cell($this->wColStatus  ,6, utf8_decode('Status'),1,0);
        $this->Cell($this->wColCodProd ,6, utf8_decode('Cod.Produto'),1,0);
        $this->Cell($this->wColDesc    ,6, utf8_decode('Descrição'),1,0);
        $this->Cell($this->wColGrade   ,6, utf8_decode('Grade'),1,0);
        $this->Cell($this->wColQtd     ,6, utf8_decode('Qtd.'),1,0);
        $this->Cell($this->wColEmb     ,6, utf8_decode('Emb.'),1,1);

        $this->SetFont('Arial',null,9);
    }

    private function addRow($row, $item, $fill)
    {
        $this->Cell($this->wN          ,$this->rowHeight, $row,1,0,'C',$fill);
        $this->Cell($this->wColCliente ,$this->rowHeight, utf8_decode(" ".self::SetStringByMaxWidth($item['CLIENTE'], $this->wColCliente)),1,0,null ,$fill);
        $this->Cell($this->wColVolume  ,$this->rowHeight, utf8_decode($item['COD_VOLUME']),1,0, "C",$fill);
        $this->Cell($this->wColStatus  ,$this->rowHeight, utf8_decode(" ".$this->arrStatusVolumes[$item['STATUS']]),1,0,null ,$fill);
        $this->Cell($this->wColCodProd ,$this->rowHeight, utf8_decode($item['COD_PRODUTO'])." ",1,0,"R" ,$fill);
        $this->Cell($this->wColDesc    ,$this->rowHeight, utf8_decode(" ".self::SetStringByMaxWidth($item['DSC_PRODUTO'], $this->wColDesc)),1,0,null ,$fill);
        $this->Cell($this->wColGrade   ,$this->rowHeight, utf8_decode(" ".$item['DSC_GRADE']),1,0,null ,$fill);
        $this->Cell($this->wColQtd     ,$this->rowHeight, utf8_decode(" ".$item['QTD_CONFERIDA']),1,0,null ,$fill);
        $this->Cell($this->wColEmb     ,$this->rowHeight, utf8_decode(" ".$item['EMBALAGEM']),1,1,null ,$fill);
    }

    private function addFooter($date) {
        $this->InFooter = true;

        $this->SetY(-15);
        $this->Cell(20, 10, utf8_decode("Data de emissão: $date"), 0,0);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'R');

        $this->InFooter = false;
    }


    private function prepare($itens) {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $i = 0;
        $this->wPage = self::GetPageWidth();

        $dth = date_format(new \DateTime(), "d/m/Y");

        $this->SetMargins(5, 5, 5);
        $this->SetFillColor(220);
        $lastKey = count($itens) - 1;
        foreach ($itens as $key => $item) {
            if (in_array(0,[$key, $i])) self::addHeader();
            self::addRow(($key + 1), $item, (($key % 2) == 0));
            $i++;
            if ($i == $this->rowLimit or $key == $lastKey) {
                self::addFooter($dth);
                $i = 0;
            }
        }

    }

    /**
     * @param $idExpedicao
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public function imprimir($idExpedicao)
    {
        $this->expedicao = $idExpedicao;

        /** @var EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $itens = $em->getRepository('wms:Expedicao')->getItensVolumeEmbalados($idExpedicao);

        if (empty($itens))
            throw new \Exception("Esta expedição não teve itens embalados conferidos ainda!");

        $this->prepare($itens);

        $this->Output("Volumes_Embalados_Expedicao-$idExpedicao.pdf",'D');
    }


}
