<?php
namespace Wms\Module\Enderecamento\Report;

use Core\Pdf,
    Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository;

class MovimentacaoProduto extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE MOVIMENTAÇÃO POR PRODUTO"), 0, 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(29,  5, utf8_decode("Data")   ,1, 0);
        $this->Cell(19, 5, utf8_decode("Tipo Movim") ,1, 0);
        $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
        $this->Cell(38, 5, utf8_decode("Pessoa") ,1, 0);
        $this->Cell(80, 5, utf8_decode("Observação") ,1, 0);
        $this->Cell(12, 5, "Qtd.", 1, 1, "C");

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
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $HistEstoqueRepo */
        $HistEstoqueRepo = $em->getRepository("wms:Enderecamento\HistoricoEstoque");

        /* @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $HistEstoqueRepo */
        $HistEstoqueRepo = $em->getRepository("wms:Enderecamento\HistoricoEstoque");

        $historicoReport = $HistEstoqueRepo->getMovimentacaoProduto($params);

        $this->SetFont('Arial', 'B', 8);
        $codProdutoAnterior = "";
        $gradeAnterior = "";
        $volumeAnterior = "";
        $qtde = 0;
        $primeiroProduto = true;
        foreach($historicoReport as $produto)
        {

            if (($codProdutoAnterior != $produto['codProduto']) || ($gradeAnterior != $produto['grade']) || ($volumeAnterior != $produto['volume'])) {

                if ($primeiroProduto == false) {
                    $this->Cell(198, 5, utf8_decode("Quantidade movimentada no período: $qtde"), 0, 1, "R");
                    $this->Cell(198, 5, "", 0, 1);
                }

                $this->Cell(198,5,utf8_decode($produto['codProduto'] . '/'. $produto['grade']. ' - '. $produto['nomeProduto']),1,1);
                $this->Cell(198,5,utf8_decode("Volume Movimentado:  - ". $produto['volume']),1,1);

                $qtde = 0;
                $codProdutoAnterior = $produto['codProduto'];
                $gradeAnterior = $produto['grade'];
                $volumeAnterior = $produto['volume'];
                $primeiroProduto = false;
            }

            if($produto['qtd']> 0)
                $tipomovim = "ENTRADA";
            else
                $tipomovim = "SAÍDA";

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(29, 5, $produto['data']->format('d/m/Y H:i:s'), 1, 0);
            $this->Cell(19, 5, utf8_decode($tipomovim), 1, 0);
            $this->Cell(20, 5, utf8_decode($produto['descricao']), 1, 0);
            $this->Cell(38, 5, substr(utf8_decode($produto['nomePessoa']),0,20), 1, 0);
            $this->Cell(80, 5, substr(utf8_decode($produto['observacao']),0,60), 1, 0);
            $this->Cell(12, 5, $produto['qtd'], 1, 1,"C");

            $qtde = $qtde + $produto['qtd'];

        }

        $this->Output('MovimentacaoProduto.pdf','D');
    }
}
