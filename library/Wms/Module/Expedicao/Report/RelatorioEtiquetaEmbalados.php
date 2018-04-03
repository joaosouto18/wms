<?php

namespace Wms\Module\Expedicao\Report;

use Wms\Util\Barcode\eFPDF,
    Wms\Util\CodigoBarras;

class RelatorioEtiquetaEmbalados extends eFPDF
{

    public function imprimirExpedicaoModelo($idExpedicao)
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $this->_em = \Zend_Registry::get('doctrine')->getEntityManager();

//        $this->SetMargins(3, 1.5, 0);
//        $this->SetAutoPageBreak(0,0);

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $volumes = $mapaSeparacaoEmbaladoRepo->getDadosEmbalado(null,$idExpedicao);

        self::bodyExpedicaoModelo1($volumes);
        $this->Output('Relatório-Volume-Embalado.pdf','I');
    }

    private function bodyExpedicaoModelo1($volumes)
    {
        $this->AddPage();

        $getY = 10;
        foreach ($volumes as $key => $volume) {
            if ($key == 0)
                $y = 30;


            if (($key+1) % 2 == 0) {
                $align = 'R';
                $xor = false;
                $this->setY($getY);
                $x = 145;
            } else {
                $align = 'L';
                $xor = true;
                $x = 15;
            }

            //monta o restante dos dados da etiqueta
            $getY = $this->getY();
            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,50));
            $this->MultiCell(170, 3.9, $impressao, 0, $align);

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('ROTA: '.$volume['DSC_ITINERARIO']."\n",0,50));
            $this->MultiCell(170, 3.9, $impressao, 0, $align);

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('PLACA: '.$volume['DSC_PLACA_CARGA']."\n",0,50));
            $this->MultiCell(170, 3.9, $impressao, 0, $align);

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('CARGA: '.$volume['COD_CARGA_EXTERNO']."\n",0,50));
            $this->MultiCell(170, 3.9, $impressao, 0, $align);

            $this->SetFont('Arial', '', 7);

            $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];
            $this->MultiCell(170, 3.9, $impressao, 0, $align);

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), $x, $y, 33, 9.5);

            if (!$xor == true) {
                $y = $y + 40;
            }

            $this->MultiCell(110, 20, '', 0, $align);
        }
    }
    
}
