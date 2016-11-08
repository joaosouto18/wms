<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf;

class MapasSemConferencia extends Pdf
{
    protected $idExpedicao;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE PRODUTOS PENDENTES DE CONFERÊNCIA - EXPEDIÇÃO " . $this->idExpedicao), 0, 1);
    }

    public function Footer()
    {
        // font
        $this->SetFont('Arial', 'B', 7);

        //Go to 1.5 cm from bottom
        $this->SetY(-20);

        $this->Cell(270, 10, utf8_decode("Relatório gerado em " . date('d/m/Y') . " às " . date('H:i:s')), 0, 0, "L");
        // font
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 15, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'R');
    }

    public function imprimir($idExpedicao, $produtos, $modelo = 1, $quebraCarga = "N")
    {
        $this->idExpedicao = $idExpedicao;
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 12);

        $this->layout1($produtos);

        $this->Output('Produtos-Mapa-Sem_Conferencia-' . $idExpedicao . '.pdf', 'D');
    }

    private function layout1($produtos)
    {
        $cargaAntiga = "";
        /** @var \Wms\Domain\Entity\Produto $produto */
        $cont =0;
        $this->AddPage();
        $this->Cell(35, 5, utf8_decode("Endereço"), "TB");
        $this->Cell(20, 5, utf8_decode("Código"), "TB");
        $this->Cell(140, 5, utf8_decode("Descrição"), "TB");
        $this->Cell(50, 5, 'Quantidade', "TB");
        $this->Cell(50, 5, 'Qtd a Conferir', "TB");
        $this->Ln();

        $linhaSeparacao = null;
        foreach ($produtos as $key => $produto) {
            if ($linhaSeparacao != $produto['DSC_LINHA_SEPARACAO']) {
                $this->SetFont('Arial', 'B', 15);
                $this->Cell(110, 5,utf8_decode($produto["DSC_LINHA_SEPARACAO"]), 0, 0, 'R');
                $this->Ln();
            }
            $this->SetFont('Arial', '', 12);
            $this->Cell(35, 5, utf8_decode($produto["DSC_DEPOSITO_ENDERECO"]), 0);
            $this->Cell(20, 5, utf8_decode($produto["COD_PRODUTO"]), 0);
            $this->Cell(140, 5, utf8_decode($produto["DSC_PRODUTO"]), 0);
            $this->Cell(50, 5, utf8_decode($produto["QTD_SEPARAR"]), 0);
            $this->Cell(50, 5, utf8_decode($produto["QTD_CONFERIR"]), 0);
            $linhaSeparacao = $produto['DSC_LINHA_SEPARACAO'];
            $this->Ln();
        }
    }

}
