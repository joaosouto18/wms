<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf,
    Wms\Domain\Entity\Expedicao\VRelProdutosRepository;

class ProdutosSemDadosLogisticos extends Pdf
{
    protected $idExpedicao;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 10, utf8_decode("RELATÓRIO DE PRODUTOS SEM DADOS LOGÍSTICOS DA EXPEDIÇÃO ". $this->idExpedicao), 0, 1);

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

    public function imprimir($idExpedicao, $produtos)
    {
        $this->idExpedicao = $idExpedicao;
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        $this->Cell(15, 5, "Produto", "TB");
        $this->Cell(145, 5, utf8_decode("Descrição"), "TB");
        $this->Cell(35, 5, "Grade", "TB");
        $this->Ln();

        /** @var \Wms\Domain\Entity\Produto $produto */
        foreach($produtos as $key => $produto) {
            $this->Cell(15, 5, $produto['id'], 0);
            $this->Cell(145, 5, $produto['descricao'], 0);
            $this->Cell(35, 5, $produto['grade'], 0);
            $this->Ln();
        }

        $this->Output('Produtos-Sem_Volumes-'.$idExpedicao.'.pdf','D');
    }
}
