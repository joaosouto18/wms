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
        $this->Cell(160, 5, utf8_decode("Descrição") ,1, 1);
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
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '-1');
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepo */
        $EstoqueRepo = $em->getRepository("wms:Enderecamento\Estoque");
        $estoqueReport = $EstoqueRepo->getEstoqueGroupByVolumns($params);

        $this->Ln();
        $codProdutoAnderior = null;
        $gradeAnterior = null;
        $volumeAnterior = null;

        $embalagemRepo = $em->getRepository("wms:Produto\Embalagem");

        $qtdEstoque = 0;
        $qtdReservaEntrada = 0;
        $qtdReservaSaida = 0;

        foreach($estoqueReport as $produto) {

            if ($volumeAnterior != $produto['VOLUME']
               || $codigoAnterior != $produto['COD_PRODUTO']
               || $gradeAnterior != $produto['DSC_GRADE']) {

                if ($produto != $estoqueReport[0]) {
                    //TOTALIZADOR

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdEstoque);
                    if(is_array($vetEstoque)) {
                        $qtdEstoqueP = implode(' + ', $vetEstoque);
                    }else{
                        $qtdEstoqueP = $vetEstoque;
                    }

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdReservaEntrada);
                    if(is_array($vetEstoque)) {
                        $qtdReservaEntradaP = implode(' + ', $vetEstoque);
                    }else{
                        $qtdReservaEntradaP = $vetEstoque;
                    }

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdReservaSaida);
                    if(is_array($vetEstoque)) {
                        $qtdReservaSaidaP = implode(' + ', $vetEstoque);
                    }else{
                        $qtdReservaSaidaP = $vetEstoque;
                    }

                    $this->SetFont('Arial','' , 8);
                    $this->Cell(93, 5, "Total", 1, 0);
                    $this->Cell(20, 5, $qtdReservaEntradaP, 1, 0,'C');
                    $this->Cell(20, 5, $qtdReservaSaidaP, 1, 0,'C');
                    $this->Cell(20, 5, $qtdEstoque, 1, 0,'C');
                    $this->Cell(40, 5,"", 1, 1,'R');
                    $this->Ln();
                }

                $qtdEstoque = 0;
                $qtdReservaEntrada = 0;
                $qtdReservaSaida   = 0;


                $produtoRepo = $em->getRepository('wms:Produto');
                $produtoEn = $produtoRepo->findOneBy(array('id'=>$produto['COD_PRODUTO'] ,'grade'=>utf8_decode($produto['DSC_GRADE'])));
                $enderecosPicking = $produtoRepo->getEnderecoPicking($produtoEn);

                $picking = "";
                if (count($enderecosPicking) > 0) {
                    $picking = " - " . reset($enderecosPicking);
                }

                //CABEÇALHO
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(12, 5, $produto['COD_PRODUTO'], 1, 0);
                $this->Cell(21, 5, utf8_decode($produto['DSC_GRADE']), 1, 0);
                $this->Cell(160, 5, substr(utf8_decode($produto['DSC_PRODUTO']),0,80) . $picking, 1, 1);
                $this->Cell(193, 5, 'VOL.: ' . substr(utf8_decode($produto['VOLUME'])     ,0,94), 1, 1);
                $this->Cell(33, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(45, 5, utf8_decode("Tipo"), 1, 0);
                $this->Cell(25, 5, utf8_decode("Reserv.Ent."), 1, 0,'C');
                $this->Cell(25, 5, utf8_decode("Reserv.Sai."), 1, 0,'C');
                $this->Cell(25, 5, utf8_decode("Qtd. Estoque"), 1, 0,'C');
                $this->Cell(40, 5, utf8_decode("Data da Entrada"), 1, 1,'R');
            }

            //CORPO DO RELATÓRIO

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $produto['QTD']);
            if(is_array($vetEstoque)) {
                $qtdEstoqueP = implode(' + ', $vetEstoque);
            }else{
                $qtdEstoqueP = $vetEstoque;
            }

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $produto['RESERVA_ENTRADA']);
            if(is_array($vetEstoque)) {
                $qtdReservaEntradaP = implode(' + ', $vetEstoque);
            }else{
                $qtdReservaEntradaP = $vetEstoque;
            }

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $produto['RESERVA_SAIDA']);
            if(is_array($vetEstoque)) {
                $qtdReservaSaidaP = implode(' + ', $vetEstoque);
            }else{
                $qtdReservaSaidaP = $vetEstoque;
            }


            $this->SetFont('Arial','' , 8);
            $this->Cell(33, 5, $produto['ENDERECO'], 1, 0);
            $this->Cell(45, 5, utf8_decode($produto['TIPO']), 1, 0);
            $this->Cell(25, 5, $qtdReservaEntradaP, 1, 0,'C');
            $this->Cell(25, 5, $qtdReservaSaidaP, 1, 0,'C');
            $this->Cell(25, 5, $qtdEstoqueP, 1, 0,'C');
            $this->Cell(40, 5, $produto['DTH_PRIMEIRA_MOVIMENTACAO'], 1, 1,'R');

            $qtdEstoque = $qtdEstoque + $produto['QTD'];
            $qtdReservaEntrada = $qtdReservaEntrada + $produto['RESERVA_ENTRADA'];
            $qtdReservaSaida   = $qtdReservaSaida   + $produto['RESERVA_SAIDA'];

            if ($produto == $estoqueReport[count($estoqueReport)-1]) {

                $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdEstoque);
                if(is_array($vetEstoque)) {
                    $qtdEstoqueP = implode(' + ', $vetEstoque);
                }else{
                    $qtdEstoqueP = $vetEstoque;
                }

                $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdReservaEntrada);
                if(is_array($vetEstoque)) {
                    $qtdReservaEntradaP = implode(' + ', $vetEstoque);
                }else{
                    $qtdReservaEntradaP = $vetEstoque;
                }

                $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($produto['COD_PRODUTO'], $produto['DSC_GRADE'], $qtdReservaSaida);
                if(is_array($vetEstoque)) {
                    $qtdReservaSaidaP = implode(' + ', $vetEstoque);
                }else{
                    $qtdReservaSaidaP = $vetEstoque;
                }

                $this->SetFont('Arial','' , 8);
                $this->Cell(78, 5, "Total", 1, 0);
                $this->Cell(25, 5, $qtdReservaEntradaP, 1, 0,'C');
                $this->Cell(25, 5, $qtdReservaSaidaP, 1, 0,'C');
                $this->Cell(25, 5, $qtdEstoqueP, 1, 0,'C');
                $this->Cell(40, 5,"", 1, 1,'R');

                //$this->Ln();
            }

            $volumeAnterior = $produto['VOLUME'];
            $codigoAnterior = $produto['COD_PRODUTO'];
            $gradeAnterior = $produto['DSC_GRADE'];
        }

        $this->Output('EstoqueReport.pdf','D');
    }
}
