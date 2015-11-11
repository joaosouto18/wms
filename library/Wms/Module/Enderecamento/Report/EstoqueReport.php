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
        $this->Cell(12,  5, utf8_decode("Código"), 1, 0);
        $this->Cell(21,  5, utf8_decode("Grade")   ,1, 0);
        $this->Cell(100, 5, utf8_decode("Descrição") ,1, 0);
        $this->Cell(60, 5, utf8_decode("Volumes") ,1, 1);
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

        $SQLorder = " ORDER BY E.COD_PRODUTO, E.DSC_GRADE, DE.DSC_DEPOSITO_ENDERECO DESC, E.NORMA, E.VOLUME, C.COD_CARACTERISTICA_ENDERECO, E.DTH_PRIMEIRA_MOVIMENTACAO, E.DTH_VALIDADE ";
        $estoqueReport = $EstoqueRepo->getEstoqueAndVolumeByParams($params, null, true, $SQLorder);
        $this->Ln();
        $codProdutoAnderior = null;
        $gradeAnterior = null;
        $codVolumeAnterior = null;

        $qtdEstoque = 0;
        $qtdReservaEntrada = 0;
        $qtdReservaSaida = 0;
        foreach($estoqueReport as $produto)
        {
            if (($codProdutoAnderior != $produto['COD_PRODUTO']) || ($gradeAnterior != $produto['DSC_GRADE']) || ($codVolumeAnterior!= $produto['COD_VOLUME'])) {

                if ($codProdutoAnderior != null) {
                    $this->SetFont('Arial','' , 8);
                    $this->Cell(93, 5, "Total", 1, 0);
                    $this->Cell(20, 5, $qtdReservaEntrada, 1, 0,'C');
                    $this->Cell(20, 5, $qtdReservaSaida, 1, 0,'C');
                    $this->Cell(20, 5, $qtdEstoque, 1, 0,'C');
                    $this->Cell(40, 5,"", 1, 1,'R');

                    $this->Ln();
                }
                $qtdEstoque = 0;
                $qtdReservado = 0;
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(12, 5, $produto['COD_PRODUTO'], 1, 0);
                $this->Cell(21, 5, utf8_decode($produto['DSC_GRADE']), 1, 0);
                $this->Cell(100, 5, substr(utf8_decode($produto['DSC_PRODUTO']),0,50), 1, 0);
                $this->Cell(60, 5, substr(utf8_decode($produto['VOLUME']),0,70), 1, 1);

                $this->Cell(33, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(60, 5, utf8_decode("Tipo"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Reserv.Ent."), 1, 0,'C');
                $this->Cell(20, 5, utf8_decode("Reserv.Sai."), 1, 0,'C');
                $this->Cell(20, 5, utf8_decode("Qtd. Estoque"), 1, 0,'C');
                $this->Cell(40, 5, utf8_decode("Data da Entrada"), 1, 1,'R');

            }

            $this->SetFont('Arial','' , 8);
            $this->Cell(33, 5, $produto['ENDERECO'], 1, 0);
            $this->Cell(60, 5, utf8_decode($produto['TIPO']), 1, 0);
            $this->Cell(20, 5, $produto['RESERVA_ENTRADA'], 1, 0,'C');
            $this->Cell(20, 5, $produto['RESERVA_SAIDA'], 1, 0,'C');
            $this->Cell(20, 5, $produto['QTD'], 1, 0,'C');
            $this->Cell(40, 5, $produto['DTH_PRIMEIRA_MOVIMENTACAO'], 1, 1,'R');
            $qtdEstoque = $qtdEstoque + $produto['QTD'];
            $qtdReservaEntrada = $qtdReservaEntrada + $produto['RESERVA_ENTRADA'];
            $qtdReservaSaida   = $qtdReservaSaida   + $produto['RESERVA_SAIDA'];
            $codProdutoAnderior = $produto['COD_PRODUTO'];
            $gradeAnterior = $produto['DSC_GRADE'];
            $codVolumeAnterior = $produto['COD_VOLUME'];

            if ($produto == $estoqueReport[count($estoqueReport)-1]) {
                $this->SetFont('Arial','' , 8);
                $this->Cell(93, 5, "Total", 1, 0);
                $this->Cell(20, 5, $qtdReservaEntrada, 1, 0,'C');
                $this->Cell(20, 5, $qtdReservaSaida, 1, 0,'C');
                $this->Cell(20, 5, $qtdEstoque, 1, 0,'C');
                $this->Cell(40, 5,"", 1, 1,'R');
            }

        }

        $this->Output('EstoqueReport.pdf','D');
    }
}
