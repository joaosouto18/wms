<?php

namespace Wms\Module\Armazenagem\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;

class EtiquetaEndereco extends Pdf
{

    public function imprimir(array $enderecos = array(), $modelo)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 3, 3);
        $this->AddPage();
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $em->getRepository('wms:Deposito\Endereco');

        $inicio = true;
        $count = 0;
        foreach($enderecos as $key => $endereco) {
            $codBarras = utf8_decode($endereco['DESCRICAO']);

            switch ($modelo) {
                case 1:
                    $produto = $enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo1($produto,$codBarras);
                    break;
                case 2:
                    $produto = $enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo2($produto,$codBarras);
                    break;
                case 3:
                    $enderecoEn = $enderecoRepo->findOneBy(array('descricao'=>$codBarras));
                    if ($enderecoEn != NULL) $this->layoutModelo3($enderecoEn);
                    break;
                case 4:
                    $this->layoutModelo4($codBarras);
                    if(end($enderecos) != $endereco)$this->AddPage();
                    break;
                default:
                    $produto = $enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo1($produto,$codBarras);
                    break;
            }
        }
        $this->Output('Etiquetas-endereco.pdf','D');
        exit;
    }

    public function layoutModelo4($codBarras){
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(5,13,"",0,0);
        $this->Cell(26,13,utf8_decode("RUA"),0,0);
        $this->Cell(32,13,utf8_decode("PREDIO"),0,0);
        $this->Cell(24,13,utf8_decode("NIVEL"),0,0);
        $this->Cell(23,13,utf8_decode("APTO"),0,1);

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0,0," ",0,1);

        $this->SetFont('Arial', 'B', 50);
        //$this->Cell(5,8,"",0,0);
        $this->Cell(95,8,$codBarras,0,0);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 5, 28 , 100);
    }

    /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
    public function layoutModelo3 ($enderecoEn) {
        $codBarras   = "999" . $enderecoEn->getId() . "999";
        $numRua      = $enderecoEn->getRua();
        $predio      = $enderecoEn->getPredio();
        $apartamento = $enderecoEn->getApartamento();
        $dscEndereco = $numRua . "." . $predio . ".__." . $apartamento;

        $this->SetFont('Arial', 'B', 69);
        $this->Cell(0,0," ",0,1);

        $posY = $this->GetY() + 6;

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(147,26,$dscEndereco,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 147, $posY , 60, 15);

        $this->Cell(5,1," ",0,1);
        $this->Line(0,$this->GetY(),297,$this->GetY());
    }

    public function layoutModelo2 ($produto, $codBarras){

        if (count($produto) <= 0) {
            $dscProduto = "";
            $dscGrade = "";
            $idProduto = "";
        } else {
            $idProduto = $produto[0]['codProduto'];
            $dscProduto = utf8_decode($produto[0]['descricao']);
            $dscGrade  = utf8_decode($produto[0]['grade']);
        }

        $lenCodBarras      = 95;
        $lenEndereco       = 112.5;
        $fontSizeCodBarras = 44;
        $fontSizeEndereco  = 28;
        $dscEndereco = $idProduto;

        $this->SetFont('Arial', 'B', 69);
        $this->Cell(0,0," ",0,1);

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(148.5,14,$dscProduto,0,1);

        $posY = $this->GetY() - 3;

        $this->SetFont('Arial', 'B', $fontSizeCodBarras);
        $this->Cell($lenCodBarras,8,$codBarras,0,0);

        $this->SetFont('Arial', 'B', $fontSizeEndereco);
        $this->Cell($lenEndereco,8,$dscEndereco,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 147, $posY , 60, 15);

        $this->Cell(5,5," ",0,1);
        $this->Line(0,$this->GetY(),297,$this->GetY());
    }

    public function layoutModelo1 ($produto, $codBarras){

        if (count($produto) <= 0) {
            $dscProduto = "";
            $dscGrade = "";
            $idProduto = "";
        } else {
            $idProduto = $produto[0]['codProduto'];
            $dscProduto = utf8_decode($produto[0]['descricao']);
            $dscGrade  = utf8_decode($produto[0]['grade']);
        }

        $lenCodBarras      = 75;
        $lenEndereco       = 112.5;
        $fontSizeCodBarras = 32;
        $fontSizeEndereco  = 18;
        if ($idProduto == "") {
            $dscEndereco = "";
        } else {
            $dscEndereco = $idProduto . "/" . $dscGrade;
        }

        $this->SetFont('Arial', 'B', 69);
        $this->Cell(0,0," ",0,1);

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(148.5,14,$dscProduto,0,1);

        $posY = $this->GetY() - 3;

        $this->SetFont('Arial', 'B', $fontSizeCodBarras);
        $this->Cell($lenCodBarras,8,$codBarras,0,0);

        $this->SetFont('Arial', 'B', $fontSizeEndereco);
        $this->Cell($lenEndereco,8,$dscEndereco,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 147, $posY , 60, 15);

        $this->Cell(5,5," ",0,1);
        $this->Line(0,$this->GetY(),297,$this->GetY());
    }

}
