<?php
namespace Wms\Module\Enderecamento\Report;

use Core\Pdf,
    Wms\Domain\Entity\Enderecamento\EstoqueRepository;

class PickingMultiplosProdutos extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE PICKINGS COM MULTIPLOS PRODUTOS"), 0, 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 5, utf8_decode("Cód. Produto") ,1, 0);
        $this->Cell(25, 5, utf8_decode("Grade") ,1, 0);
        $this->Cell(115, 5, utf8_decode("Produto") ,1, 0);
        $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
        $this->Cell(17, 5, "Qtd. Prod.", 1, 1);
    }

    public function layout()
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 5, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();
    }

    public function Footer()
    {
        // font
        $this->SetFont('Arial','B',7);

        //Go to 1.5 cm from bottom
        $this->SetY(-20);

        $this->Cell(270, 10, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        // font
        $this->SetFont('Arial','',8);
        $this->Cell(0,15,utf8_decode('Página ').$this->PageNo(),0,0,'R');
    }


    public function init($params = array())
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EstoqueRepo */
        $EstoqueRepo = $em->getRepository("wms:Deposito\Endereco");

        $estoqueReport = $EstoqueRepo->getPickingMultiplosProdutos($params);

        //var_dump($estoqueReport);exit;

        foreach($estoqueReport as $estoque)
        {
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(20, 5, utf8_decode($estoque['COD_PRODUTO']), 1, 0);
            $this->Cell(25, 5, utf8_decode($estoque['GRADE']), 1, 0);
            $this->Cell(115, 5, utf8_decode($estoque['PRODUTO']), 1, 0);
            $this->Cell(20, 5, " " . utf8_decode($estoque['DESCRICAO']), 1, 0);
            $this->Cell(17, 5,$estoque['QTD'], 1, 1);
        }

        $this->Output('PickingMultiplosProdutos.pdf','D');
    }
}
