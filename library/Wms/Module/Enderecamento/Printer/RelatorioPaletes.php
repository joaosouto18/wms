<?php

namespace Wms\Module\Enderecamento\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Enderecamento\Palete;

class RelatorioPaletes extends Pdf
{

    protected $idRecebimento;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(10, 10, utf8_decode("RELATÓRIO DE PALETES DO RECEBIMENTO ". $this->idRecebimento), 0, 1);
    }

    public function imprimir(array $paletes = array(), $idRecebimento)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        $this->idRecebimento = $idRecebimento;

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $PaleteRepo */
        $paleteRepo   = $em->getRepository('wms:Enderecamento\Palete');

        $this->SetMargins(7, 7, 0);
        $this->SetFont('Arial', 'B', 10);
        $this->AddPage();

        $this->Cell(18,5,"U.M.A.","TB");
        $this->Cell(18,5,utf8_decode("Código"),"TB");
        $this->Cell(30,5,"Grade","TB");
        $this->Cell(105,5,"Produto","TB");
        $this->Cell(25,5,utf8_decode("End.Pulmão"),"TB");
        $this->Cell(13,5,"Qtde","TB");
        $this->Cell(25,5,"End.Picking","TB");
        $this->Cell(45,5,"Unitizador","TB");
        $this->Ln();

        $this->SetFont('Arial', '', 10);
        foreach($paletes as $palete) {
            $this->layout($palete, $paleteRepo);
        }

        $this->Output('RelatorioPaletes.pdf','D');

    }

    protected function layout($palete, $paleteRepo)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $embalagemRepo = $em->getRepository("wms:Produto\Embalagem");
        $volumeRepo = $em->getRepository("wms:Produto\Volume");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($palete);
        $enderecoEn = $paleteEn->getDepositoEndereco();
        $enderecoPicking = "";
        $enderecoSelecionado = "";

        $produtosEn = $paleteEn->getProdutos();

        if (($produtosEn[0]->getCodProdutoEmbalagem() == NULL)) {
            $embalagemEn = $volumeRepo->findOneBy(array('id'=> $produtosEn[0]->getCodProdutoVolume()));
        } else {
            $embalagemEn = $embalagemRepo->findOneBy(array('id'=> $produtosEn[0]->getCodProdutoEmbalagem()));
        }

        if ($embalagemEn->getEndereco() != null) {
            $enderecoPicking = $embalagemEn->getEndereco()->getDescricao();
        }

        if ($enderecoEn != NULL) {
            $enderecoSelecionado = $enderecoEn->getDescricao();
        }

        $this->Cell(18,5,$palete,0,0);
        $this->Cell(18,5,$embalagemEn->getProduto()->getId(),0,0);
        $this->Cell(30,5,$embalagemEn->getProduto()->getGrade(),0,0);
        $this->Cell(105,5,$embalagemEn->getProduto()->getDescricao(),0,0);
        $this->Cell(25,5,$enderecoSelecionado,0,0);
        $this->Cell(13,5,$produtosEn[0]->getQtd(),0,0);
        $this->Cell(25,5,$enderecoPicking,0,0);
        $this->Cell(45,5,$paleteEn->getUnitizador()->getDescricao(),0,1);
    }

    function Footer()
    {
        // Go to 1.5 cm from bottom
        $this->SetY(-15);
        // Select Arial italic 8
        $this->SetFont('Arial','I',8);
        // Print centered page number
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo(),0,0,'C');
    }

}
