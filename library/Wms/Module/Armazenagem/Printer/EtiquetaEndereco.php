<?php

namespace Wms\Module\Armazenagem\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;

class EtiquetaEndereco extends Pdf
{

    public $lado;
    public $y;
    public $count;

    public function imprimir(array $enderecos = array(), $modelo)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 0, 3);
        $this->AddPage();
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $em->getRepository('wms:Deposito\Endereco');

        $this->lado = "E";
        $inicio = true;
        $count = 0;
        $this->y=0;
        $this->count = 0;
        foreach($enderecos as $key => $endereco) {
            $codBarras = utf8_decode($endereco['DESCRICAO']);

            switch ($modelo) {
                case 1:
                    $produtos = $enderecoRepo->getProdutoByEndereco($codBarras,false);
                    foreach ($produtos as $produto){
                        $this->layoutModelo1($produto,$codBarras);
                    }
                    if (count($produtos) <= 0){
                        $this->layoutModelo1(null,$codBarras);
                    }
                    break;
                case 2:
                    $produtos = $enderecoRepo->getProdutoByEndereco($codBarras,false);
                    foreach ($produtos as $produto){
                        $this->layoutModelo2($produto,$codBarras);
                    }
                    if (count($produtos) <= 0){
                        $this->layoutModelo2(null,$codBarras);
                    }
                    break;
                case 3:
                    $enderecoEn = $enderecoRepo->findOneBy(array('descricao'=>$codBarras));
                    if ($enderecoEn != NULL) $this->layoutModelo3($enderecoEn);
                    break;
                case 4:
                    $this->layoutModelo4($codBarras);
                    if(end($enderecos) != $endereco)$this->AddPage();
                    break;
                case 5:
                    $this->layoutModelo5($codBarras);
                    break;
                case 6:
                    $this->layoutModelo6($codBarras);
                    break;
                case 7:
                    $produto = $enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo7($produto,$codBarras);
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
            $idProduto = $produto['codProduto'];
            $dscProduto = utf8_decode($produto['descricao']);
            $dscGrade  = utf8_decode($produto['grade']);
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
            $idProduto = $produto['codProduto'];
            $dscProduto = utf8_decode($produto['descricao']);
            $dscGrade  = utf8_decode($produto['grade']);
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

    public function layoutModelo5 ( $codBarras){

        if ($this->count >= 20) {
            $this->AddPage();
            $this->count = 0;
        }
        $curY = $this->GetY();
        if ($this->lado == "E") {
            $this->y = $curY;
            $this->lado = "D";
            $x = 0;
        } else {
            $this->lado = "E";
            $x = 100;
        }

        $this->SetY($this->y);

        $lenCodBarras      = 75;
        $fontSizeCodBarras = 32;
        $this->Cell(0,2,"    ",0,1);

        $this->SetX($x);
        $this->SetFont('Arial', 'B', $fontSizeCodBarras);
        $this->Cell($lenCodBarras,20,"        " . $codBarras,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , $x+30, $this->GetY() - 5 , 60, 15);

        $this->count = $this->count +1;
        $this->Cell(5,5," ",0,1);
        $this->Line(110,0,110,$this->GetY() + 5);
        $this->Line(0,$this->GetY() + 5,297,$this->GetY() + 5);
    }

    public function layoutModelo6 ($codBarras){
        $this->Cell(5,3,"",0,1);
        $enderecos = explode(".",$codBarras);
        $rua = substr($enderecos[0],0);
        $predio = substr($enderecos[1],1);
        $nivel = substr($enderecos[2],1);
        $apartamento = substr($enderecos[3],0);
        $codBarras = "$rua.$predio.$nivel.$apartamento";
        $this->SetX(5);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(7,13,"",0,0);
        $this->Cell(19,13,utf8_decode("RUA"),0,0);
        $this->Cell(22,13,utf8_decode("PREDIO"),0,0);
        $this->Cell(18,13,utf8_decode("NIVEL"),0,0);
        $this->Cell(23,13,utf8_decode("APTO"),0,1);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0,0," ",0,1);
        $this->SetX(7);
        $this->SetFont('Arial', 'B', 48);
        $this->Cell(95,8,$codBarras,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 4, $this->GetY()+5 , 100);
        $this->Cell(95,5," ",0,1);
        if ($nivel == 0) {
            $this->Image(APPLICATION_PATH . '/../data/seta1.png', 88, $this->GetY()-22 , 13,20);
        } else {
//            $this->Image(APPLICATION_PATH . '/../data/seta2.png', 88, $this->GetY()-23 , 13,20);
        }
        $this->Cell(95,10," ",0,1);

    }

    public function layoutModelo7($produto, $codBarras)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(5,13,"",0,0);
        $this->Cell(24,13,utf8_decode("RUA"),0,0);
        $this->Cell(28,13,utf8_decode("PREDIO"),0,0);
        $this->Cell(20,13,utf8_decode("NIVEL"),0,0);
        $this->Cell(19,13,utf8_decode("APTO"),0,1);

        $this->SetFont('Arial', 'B', 45);
        //$this->Cell(5,8,"",0,0);
        $this->Cell(95,8,$codBarras,0,1);

        if (isset($produto[0]) && !empty($produto[0])) {
            $dscProduto = utf8_decode($produto[0]['descricao']);
            if (strlen($dscProduto) >=26) {
                $this->SetFont('Arial', 'B', 10);
            } else {
                $this->SetFont('Arial', 'B', 18);
            }
            $this->Cell(0,25,$dscProduto,0,0);
        }

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 5, 45 , 90);
    }

}
