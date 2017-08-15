<?php

namespace Wms\Module\Enderecamento\Report;

use Core\Pdf,
    Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository;

class MovimentacaoProduto extends Pdf {

    public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);
    }

    public function Header() {
        //Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE MOVIMENTAÇÃO POR PRODUTO"), 0, 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(15, 5, utf8_decode("Produto"), 1, 0);
        $this->Cell(15, 5, utf8_decode("Grade"), 1, 0);
        $this->Cell(45, 5, utf8_decode("Descrição"), 1, 0);
        $this->Cell(29, 5, utf8_decode("Data"), 1, 0);
        $this->Cell(18, 5, utf8_decode("Tipo Movim"), 1, 0);
        $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
        $this->Cell(20, 5, utf8_decode("Pessoa"), 1, 0);
        $this->Cell(17, 5, utf8_decode("Validade"), 1, 0);
        $this->Cell(68, 5, utf8_decode("Observação"), 1, 0);
        $this->Cell(11, 5, "Qtd.", 1, 0, "C");
        $this->Cell(15.5, 5, "Saldo Ant.", 1, 0, "C");
        $this->Cell(15.5, 5, "Saldo Fim", 1, 1, "C");
    }

    public function layout() {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(4, 5, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();
    }

    public function Footer() {
        // font
        $this->SetFont('Arial', 'B', 7);

        //Go to 1.5 cm from bottom
        $this->SetY(-20);

        $this->Cell(270, 10, utf8_decode("Relatório gerado em " . date('d/m/Y') . " às " . date('H:i:s')), 0, 0, "L");
        // font
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 15, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'R');
    }

    public function init($params = array()) {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(4, 0, 0);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $HistEstoqueRepo */
        $HistEstoqueRepo = $em->getRepository("wms:Enderecamento\HistoricoEstoque");

        $historicoReport = $HistEstoqueRepo->getMovimentacaoProduto($params);

        $this->SetFont('Arial', 'B', 8);
        $codProdutoAnterior = "";
        $gradeAnterior = "";
        $volumeAnterior = "";
        $qtde = 0;
        $primeiroProduto = true;
        foreach ($historicoReport as $produto) {

            /* if (($codProdutoAnterior != $produto['codProduto']) || ($gradeAnterior != $produto['grade']) || ($volumeAnterior != $produto['volume'])) {

              if ($primeiroProduto == false) {
              $this->Cell(198, 5, utf8_decode("Quantidade movimentada no período: $qtde"), 0, 1, "R");
              $this->Cell(198, 5, "", 0, 1);
              }

              $this->Cell(198, 5, utf8_decode($produto['codProduto'] . '/' . $produto['grade'] . ' - ' . $produto['nomeProduto']), 1, 1);
              $this->Cell(198, 5, utf8_decode("Volume Movimentado:  - " . $produto['volume']), 1, 1);

              $qtde = 0;
              $codProdutoAnterior = $produto['codProduto'];
              $gradeAnterior = $produto['grade'];
              $volumeAnterior = $produto['volume'];
              $primeiroProduto = false;
              } */

            if ($produto['qtd'] > 0)
                $tipomovim = "ENTRADA";
            else
                $tipomovim = "SAIDA";

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(15, 5, $produto['codProduto'], 1, 0);
            $this->Cell(15, 5, $produto['grade'], 1, 0);
            $this->Cell(45, 5, self::SetStringByMaxWidth(utf8_decode($produto['nomeProduto']), 45), 1, 0);
            $this->Cell(29, 5, $produto['data']->format('d/m/Y H:i:s'), 1, 0);
            $this->Cell(18, 5, $tipomovim, 1, 0);
            $this->Cell(20, 5, $produto['descricao'], 1, 0);
            $this->Cell(20, 5, self::SetStringByMaxWidth(utf8_decode($produto['nomePessoa']), 20), 1, 0);
            $validade = (!is_null($produto['validade'])) ? $produto['validade']->format('d/m/Y') : "-";
            $this->Cell(17, 5, $validade, 1, 0);
            $this->Cell(68, 5, self::SetStringByMaxWidth(utf8_decode($produto['observacao']), 64), 1, 0);
            $this->Cell(11, 5, $produto['qtd'], 1, 0, "C");
            $this->Cell(15.5, 5, $produto['saldoAnterior'], 1, 0, "C");
            $this->Cell(15.5, 5, $produto['saldoFinal'], 1, 1, "C");

            $qtde = $qtde + $produto['qtd'];
        }

        $this->Output('MovimentacaoProduto.pdf', 'D');
    }

}
