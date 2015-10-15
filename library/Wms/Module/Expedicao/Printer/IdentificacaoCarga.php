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

        $cargaRepo = $em->getRepository("wms:Expedicao\Carga")->findOneBy(array('codCargaExterno'=>$codCargaExterno,
                                                                                'codExpedicao'=>$idExpedicao));

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetFont('Arial',  null, 8);
        $this->AddPage();
        $this->Cell(20, 4, "teste",0,1);
        $this->Cell(20, 4, $idExpedicao,0,1);
        $this->Cell(20, 4, $codCargaExterno,0,1);
        $this->Output('IdentificaçãoCarga.pdf','D');

    }
}
