<?php
namespace Wms\Module\Enderecamento\Report;

use Core\Pdf,
    Wms\Domain\Entity\Enderecamento\EstoqueRepository;

class EstoqueReport extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE ESTOQUE"), 0, 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(14,  5, utf8_decode("Código"), 1, 0);
        $this->Cell(20,  5, utf8_decode("Grade")   ,1, 0);
        $this->Cell(70, 5, utf8_decode("Descrição") ,1, 0);
        $this->Cell(20, 5, utf8_decode("Picking") ,1, 0);
        $this->Cell(20, 5, utf8_decode("Pulmão"), 1, 0);
        $this->Cell(29, 5, utf8_decode("Dth Entrada") ,1, 0);
        $this->Cell(22, 5, "Qtde", 1, 1);
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

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepo */
        $EstoqueRepo = $em->getRepository("wms:Enderecamento\Estoque");

        $estoqueReport = $EstoqueRepo->getEstoquePulmao($params);

        foreach($estoqueReport as $produto)
        {
            $codProduto = $produto[0]['codProduto'];
            $grade = $produto[0]['grade'];
            $nomeProduto = $produto[0]['produto'];
            $picking = $produto[0]['enderecoPicking'];

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(14, 5, $codProduto, 1, 0);
            $this->Cell(20, 5, utf8_decode($grade), 1, 0);
            $this->Cell(70, 5, substr(utf8_decode($nomeProduto),0,40), 1, 0);
            $this->Cell(20, 5, utf8_decode($picking), 1, 0);
            $this->Cell(20, 5, "", 1, 0);
            $this->Cell(29, 5, "", 1, 0);
            $this->Cell(22, 5, "", 1, 1);

            $total = 0;

            foreach($produto as $estoque)
            {
                $descricao = $estoque['descricao'];
                if ($descricao != NULL) {
                    $this->SetFont('Arial', 'B', 8);

                    $quantidade = $estoque['qtd'];
                    $dthEntrada = $estoque['dtPrimeiraEntrada']->format('d/m/Y H:i:s');

                    $this->Cell(14, 5, "", 0, 0);
                    $this->Cell(20, 5, "", 0, 0);
                    $this->Cell(70, 5, "", 0, 0);
                    $this->Cell(20, 5, "", 0, 0);
                    $this->Cell(20, 5, utf8_decode($descricao), 1, 0);
                    $this->Cell(29, 5, utf8_decode($dthEntrada), 1, 0);
                    $this->Cell(22, 5,utf8_decode($quantidade), 1, 1);

                    $total = $total + $quantidade;
                }
            }

            $this->Cell(14, 5, "", 0, 0);
            $this->Cell(20, 5, "", 0, 0);
            $this->Cell(70, 5, "", 0, 0);
            $this->Cell(20, 5, "", 0, 0);
            $this->Cell(20, 5, "", 0, 0);
            $this->Cell(29, 5, "", 0, 0);
            $this->Cell(22, 5,"Total: ".$total, 1, 1);

            $this->Cell(30,5,"",0,1);
        }

        $this->Output('EstoqueReport.pdf','D');
    }
}
