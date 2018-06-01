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

    public function imprimir($idExpedicao, $produtos, $embalagemRepo)
    {
        $this->idExpedicao = $idExpedicao;
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 12);

        $this->layout1($produtos, $embalagemRepo);

        $this->Output('Produtos-Mapa-Sem_Conferencia-' . $idExpedicao . '.pdf', 'D');
    }

    private function layout1($produtos, $embalagemRepo)
    {
        $this->AddPage();
        $this->Cell(35, 5, "Endereço", "TB");
        $this->Cell(20, 5, "Código", "TB");
        $this->Cell(120, 5, utf8_decode("Descrição"), "TB");
        $this->Cell(30, 5, 'Qtd Total', "TB");
        $this->Cell(30, 5, 'Conferida', "TB");
        $this->Cell(10, 5, '', "TB");
        $this->Cell(30, 5, 'Qtd Conferir', "TB");
        $this->Ln();

        $quebra = null;
        foreach ($produtos as $key => $produto) {
            $qtdConferirI = '';
            $qtdConferirII = '';
            $embalagens = $embalagemRepo->findBy(array('codProduto' => $produto["COD_PRODUTO"], 'grade' => $produto['DSC_GRADE']), array('quantidade' => 'DESC'));
            foreach ($embalagens as $embalagem) {
                $qtdConferir = floor($produto["QTD_CONFERIR"] % $embalagem->getQuantidade());
                $qtdConferirI = floor($produto["QTD_CONFERIR"] / $embalagem->getQuantidade());
                if ($qtdConferirI == 0)
                    $qtdConferirI = '';
                else
                    $qtdConferirI = $qtdConferirI . ' ' . $embalagem->getDescricao();
                break;
            }
            $embalagens = $embalagemRepo->findBy(array('codProduto' => $produto["COD_PRODUTO"], 'grade' => $produto['DSC_GRADE']), array('quantidade' => 'ASC'));
            foreach ($embalagens as $embalagem) {
                $qtdConferirII = floor(($qtdConferir) / $embalagem->getQuantidade());
                if ($qtdConferirII == 0)
                    $qtdConferirII = '';
                else
                    $qtdConferirII = $qtdConferirII . ' '  . $embalagem->getDescricao();
                break;
            }
            if ($quebra != $produto['DSC_QUEBRA']) {
                $this->SetFont('Arial', 'B', 15);
                $this->Cell(110, 5,utf8_decode(trim($produto['DSC_QUEBRA'])), 0, 0, 'R');
                $this->Ln();
            }

            $this->SetFont('Arial', '', 12);
            $this->Cell(35, 5, utf8_decode($produto["DSC_DEPOSITO_ENDERECO"]), 0);
            $this->Cell(20, 5, utf8_decode($produto["COD_PRODUTO"]), 0);
            $this->Cell(120, 5, utf8_decode(substr($produto["DSC_PRODUTO"],0,45)), 0);
            $this->Cell(30, 5, utf8_decode($produto["QTD_SEPARAR"]), 0);
            $this->Cell(30, 5, utf8_decode($produto["QTD_SEPARAR"] - $produto["QTD_CONFERIR"]), 0);
            $this->Cell(30, 5, utf8_decode($qtdConferirI), 0);
            $this->Cell(30, 5, utf8_decode($qtdConferirII), 0);
            $quebra = $produto['DSC_QUEBRA'];
            $this->Ln();
        }
    }

}
