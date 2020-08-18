<?php

namespace Wms\Module\Armazenagem\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;
use Wms\Math;
use Wms\Util\Endereco;

class EtiquetaEndereco extends Pdf
{

    public $lado;
    public $y;
    public $count;

    /*** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository  */
    private $enderecoRepo;

    public function imprimir(array $enderecos = array(), $modelo, $unico = false, $quantidadeByPage = null)
    {

        $this->em = \Zend_Registry::get('doctrine')->getEntityManager();
        $this->enderecoRepo   = $this->em->getRepository('wms:Deposito\Endereco');

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 0, 3);
        $this->AddPage();

        $this->lado = "E";
        $this->y=0;
        $this->count = 0;
        $qtd = 0;
        $arrPares = array();

        foreach($enderecos as $key => $endereco) {
            $codBarras = utf8_decode($endereco['DESCRICAO']);
            switch ((int)$modelo) {
                case 1:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras,false,false,false);

                    if ($quantidadeByPage != null) {
                        if ((($key) % $quantidadeByPage) == 0 && $key != 0) $this->AddPage();
                    }


                    if (count($produtos) <= 0){
                        $this->layoutModelo1(null,$codBarras);
                    } else {
                        foreach ($produtos as $produto){
                            $this->layoutModelo1($produto,$codBarras);
                        }
                    }
                    break;
                case 2:
                    if (is_int($key / 10) && $key > 0) $this->AddPage();
                    $this->layoutModelo2(null,$codBarras);
                    break;
                case 3:
                    $enderecoEn = $this->enderecoRepo->findOneBy(array('descricao'=>$codBarras));
                    if ($enderecoEn != NULL) $this->layoutModelo3($enderecoEn);
                    break;
                case 4:
                    if($key > 0) $this->AddPage();
                    $this->layoutModelo4($codBarras);
                    break;
                case 5:
                    $this->layoutModelo5($codBarras);
                    break;
                case 6:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo6($produtos,$codBarras);
                    break;
                case 7:
                    $produto = $this->enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->layoutModelo7($produto,$codBarras);
                    break;
                case 8:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras,false);
                    foreach ($produtos as $produto){
                        $this->layoutModelo8($produto,$codBarras);
                    }
                    if (count($produtos) <= 0){
                        $this->layoutModelo8(null,$codBarras);
                    }
                    break;
                case 9:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras,$unico);
                    if (count($produtos) <= 0){
                        $this->layoutModelo9(null,$codBarras);
                    } else {
                        foreach ($produtos as $i => $produto){
                            $this->layoutModelo9($produto,$codBarras);
                            if ($i < (count($produtos) - 1))
                                $this->AddPage();
                        }
                    }
                    break;
                case 10:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras,false);
                    if (empty($produtos)){
                        $arrPares[] = array('produtos' => null, 'codBarras' => $codBarras);
                        if (count($arrPares) == 3) {
                            $this->layoutModelo10($arrPares);
                            $arrPares = array();
                            if ($key < (count($enderecos) - 1)) {
                                $this->AddPage();
                            }
                        }

                    } else {
                        foreach ($produtos as $i => $produto){
                            $arrPares[] = array('produtos' => $produto, 'codBarras' => $codBarras);
                            if (count($arrPares) == 3) {
                                $this->layoutModelo10($arrPares);
                                $arrPares = array();
                                if ($key < (count($enderecos) - 1) || ($key == (count($enderecos) - 1) && $i < (count($produtos) - 1))) {
                                    $this->AddPage();
                                }
                            }

                        }
                    }

                    if ($key == (count($enderecos) - 1) && !empty($arrPares)) {
                        $this->layoutModelo10($arrPares);
                    }
                    break;
                case 11:
                    $produtosEndereco = $this->enderecoRepo->getProdutoByEndereco($codBarras,false);

                    $produtos = array();
                    foreach ($produtosEndereco as $prod){
                        if (!isset($produtos[$prod['codProduto']][$prod['grade']])){
                            $produtos[$prod['codProduto']][$prod['grade']] = array(
                                'codProduto'=>$prod['codProduto'],
                                'grade'=>$prod['grade'],
                                'descricao'=>$prod['descricao']
                            );
                        }
                    }
                    $qtd = $qtd +1;
                    if ($qtd >=10) {
                        $this->AddPage();

                    }
                    $this->layoutModelo11($produtos,$codBarras);

                    break;
                case 15:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras);
                    $this->SetAutoPageBreak(false);
                    if($key > 0) $this->AddPage();
                    $this->layoutModelo15($produtos,$codBarras);
                    break;
                case 13:
                    if($key > 0) $this->AddPage();
                        $this->layoutModelo13($codBarras);
                    break;
                case 14:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras);
                    if($key > 0) $this->AddPage();
                    $this->layoutModelo14($produtos,$codBarras);
                    break;
                case 16:
                    if($key > 0) $this->AddPage();
                        $this->layoutModelo16($codBarras);
                    break;
                case 17:
                    if($key > 0) $this->AddPage();
                        $this->layoutModelo17($codBarras);
                    break;
                default:
                    $produtos = $this->enderecoRepo->getProdutoByEndereco($codBarras, false);
                    if (count($produtos) <= 0){
                        $this->layoutModelo1(null,$codBarras);
                    } else {
                        foreach ($produtos as $produto){
                            $this->layoutModelo1($produto,$codBarras);
                        }
                    }
                    break;
            }
        }

        $this->Output('etiqueta.pdf','I');

    }

    public function layoutModelo4($codBarras){
        $enderecoEntity = $this->enderecoRepo->findOneBy(array('descricao' => $codBarras));

        $rua = $enderecoEntity->getRua();
        $predio = $enderecoEntity->getPredio();
        $nivel = $enderecoEntity->getNivel();
        $apto = $enderecoEntity->getApartamento();


        $this->SetFont('Arial', 'B', 12);
        $this->Cell(5,13,"",0,0);
        $this->Cell(23,13,utf8_decode("RUA"),0,0);
        $this->Cell(32,13,utf8_decode("PREDIO"),0,0);
        $this->Cell(24,13,utf8_decode("NIVEL"),0,0);
        $this->Cell(23,13,utf8_decode("APTO"),0,1);


        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0,0," ",0,1);

        $this->SetFont('Arial', 'B', 50);
        $this->Cell(24,8,"$rua.",0,0);
        $this->Cell(28,8,"$predio.",0,0);
        $this->Cell(21,8,"$nivel.",0,0);
        $this->Cell(18,8,"$apto",0,0);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","","$rua.$predio.$nivel.$apto")) , 5, 28 , 85);
    }

    public function layoutModelo13($codBarras){
        $this->InFooter = true;
        $this->Cell(5,13,"",0,0);
        $this->SetFont('Arial', 'B', 8);

        $this->Cell(22,12,utf8_decode("RUA"),0,0);
        $this->Cell(25,12,utf8_decode("PREDIO"),0,0);
        $this->Cell(24,12,utf8_decode("NIVEL"),0,0);
        $this->Cell(0,12,utf8_decode("APTO"),0,1);

        $this->SetFont('Arial', 'B', 41);
        $this->Cell(22,10,$codBarras,0,0);
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
        if($dscProduto == "") {
            $this->Cell(148.5,13,"             Rua      Predio     Nivel    Apto.",0,1);
        } else {
            $this->Cell(148.5,13,'            '.$dscEndereco. ' - '.$dscProduto,0,1);
        }

        $posY = $this->GetY() - 3;

        $this->SetFont('Arial', 'B', $fontSizeCodBarras);
        $this->Cell($lenCodBarras,9,'     '.$codBarras,0,0);

        $this->SetFont('Arial', 'B', $fontSizeEndereco);
        $this->Cell($lenEndereco,8,'     ',0,1);

        $posYSeta = $posY - 8;
        $enderecos = explode(".",$codBarras);
        $nivel = substr($enderecos[2],1);

        if ($nivel == 0) {
            $this->Image(APPLICATION_PATH . '/../data/seta1.png', 5, $posYSeta, 13, 20);
        } else if ($nivel == 1) {
            $this->Image(APPLICATION_PATH . '/../data/seta2.png', 5, $posYSeta, 13, 20);
        }

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 140, $posY , 60, 15);

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

        $lenCodBarras      = 70;
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

        $strWidth = $this->GetStringWidth($dscEndereco);
        if (Math::compare($strWidth, $lenEndereco)) {
            $fontSizeEndereco = 14;
            $str1 = substr($dscEndereco, 0, strlen($dscEndereco) / 2);
            $str2 = substr($dscEndereco, (strlen($dscEndereco) / 2), strlen($dscEndereco));

            $this->SetFont('Arial', 'B', $fontSizeEndereco);
            $this->SetXY(80, $posY);
            $this->MultiCell($lenEndereco,8,$str1);
            $this->SetXY(80, $posY + 5);
            $this->MultiCell($lenEndereco,8,$str2);
        } else {
            $this->SetFont('Arial', 'B', $fontSizeEndereco);
            $this->Cell($lenEndereco,8,$dscEndereco,0,1);
        }

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 147, $posY , 60, 15);

        $this->Cell(5,15," ",0,1);
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

    public function layoutModelo6 ($produto, $codBarras){
        $this->Cell(5,3,"",0,1);
        $arrEndereco = Endereco::separar($codBarras);
        $codBarras = implode('.',$arrEndereco);
        $this->SetX(5);
        $wRua = 19;
        $wPredio = 22;
        $wNivel = 18;
        $wApto = 23;
        $wTotal = $wRua + $wPredio + $wNivel + $wApto;
        if (strlen(reset($produto)['codProduto']) <= 8)
            $tamanhoCodigo = 35;
        else
            $tamanhoCodigo = 19;

        $this->SetFont('Arial', 'B', $tamanhoCodigo);
//        $this->Cell(30,13,"",0,0, 'C');
        $this->Cell(0,13,reset($produto)['codProduto'],0,1, 'C');
        $this->Cell(17,13,"",0,0);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($wRua,13,utf8_decode("RUA"),0,0);
        $this->Cell($wPredio,13,utf8_decode("PREDIO"),0,0);
        $this->Cell($wNivel,13,utf8_decode("NIVEL"),0,0);
        $this->Cell($wApto,13,utf8_decode("APTO"),0,1);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0,0," ",0,1);
        $this->SetX(17);
        $count = strlen(str_replace('.','',$codBarras));
        $fX = ($wTotal / $count) * 4.12;
        $this->SetFont('Arial', 'B', $fX);
        $this->Cell($wTotal,8,$codBarras,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 14, $this->GetY()+3 , 90);
        $this->Cell(10,0," ",0,1);
        if (substr($arrEndereco['nivel'], -1) == 0) {
            $this->Image(APPLICATION_PATH . '/../data/seta1.png', 0, $this->GetY(),13,20);
        } else {
            $this->Image(APPLICATION_PATH . '/../data/seta2.png', 0, $this->GetY(), 13,20);
        }
//        $this->Cell(95,10," ",0,1);

    }

    public function layoutModelo14 ($produto, $codBarras){
        $this->Cell(5,3,"",0,1);
        $arrEndereco = Endereco::separar($codBarras);
        $codBarras = implode('.',$arrEndereco);
        $this->SetXY(5,1);
        $wRua = 19;
        $wPredio = 22;
        $wNivel = 18;
        $wApto = 23;
        $wTotal = $wRua + $wPredio + $wNivel + $wApto;
        if (strlen(reset($produto)['codProduto']) <= 8)
            $tamanhoCodigo = 15;
        else
            $tamanhoCodigo = 15;

        $this->InFooter = true;
        $this->SetFont('Arial', 'B', $tamanhoCodigo);
        $this->MultiCell(0,6, reset($produto)['codProduto'].' - '.reset($produto)['descricao'],0,'C');
        $this->Cell(17,13,"",0,0);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($wRua,13,utf8_decode("RUA"),0,0);
        $this->Cell($wPredio,13,utf8_decode("PREDIO"),0,0);
        $this->Cell($wNivel,13,utf8_decode("NIVEL"),0,0);
        $this->Cell($wApto,13,utf8_decode("APTO"),0,1);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0,0," ",0,1);
        $this->SetX(17);
        $count = strlen(str_replace('.','',$codBarras));
        $fX = ($wTotal / $count) * 4.12;
        $this->SetFont('Arial', 'B', $fX);
        $this->Cell($wTotal,8,$codBarras,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 14, $this->GetY()+3 , 90);
        $this->InFooter = false;
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

    public function layoutModelo8 ($produto, $codBarras){

        if (count($produto) <= 0) {
            $dscProduto = "";
            $dscGrade = "";
            $idProduto = "";
        } else {
            $idProduto = $produto['codProduto'];
            $dscProduto = utf8_decode($produto['descricao']);
            $dscGrade  = utf8_decode($produto['grade']);
        }

        $enderecos = explode(".",$codBarras);
        $rua = substr($enderecos[0],0);
        $predio = substr($enderecos[1],1);
        $nivel = substr($enderecos[2],1);
        $apartamento = substr($enderecos[3],0);

        $lenCodBarras      = 95;
        $lenEndereco       = 112.5;
        $fontSizeCodBarras = 44;
        $fontSizeEndereco  = 28;
        $dscEndereco       = $idProduto;

        $this->SetFont('Arial', 'B', 69);
        $this->Cell(0,0," ",0,1);

        $this->SetFont('Arial', 'B', 18);
        if($dscProduto == "") {
            $this->Cell(148.5,14,"               Rua      Predio     Nivel    Apto.",0,1);
        } else {
            $this->Cell(148.5,14,'     '.$dscProduto,0,1);
        }

        $posY = $this->GetY() - 3;

        $this->SetFont('Arial', 'B', $fontSizeCodBarras);
        $this->Cell($lenCodBarras,4,'      '.$codBarras,0,0);

        $this->SetFont('Arial', 'B', $fontSizeEndereco);
        $this->Cell($lenEndereco,8,'      '.$dscEndereco,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 135, $posY , 60, 15);

        $posYSeta = $posY - 8;
        if ($nivel == 0) {
            $this->Image(APPLICATION_PATH . '/../data/seta1.png', 5, $posYSeta, 13, 20);
        } else if ($nivel == 1) {
            $this->Image(APPLICATION_PATH . '/../data/seta2.png', 5, $posYSeta, 13, 20);
        }

        $this->Cell(5,5," ",0,1);
        $this->Line(0,$this->GetY(),297,$this->GetY());
    }


    public function layoutModelo11 ($produtos, $codBarras){

        //Celula para espaço em branco
        $this->Cell(0,0," ",0,1);
        $posYIni = $this->GetY();
        $posXIni = $this->getX();
        
        $linhaX = 14;
        
        if(count($produtos) > 4){
            $linhaX = count($produtos) * 4.5;
        }
        
        //Imprime a descrição do Endereço XX.XXX.XX.XX
        $this->SetFont('Arial', 'B', 32);
        $this->SetX(138);
        $this->Cell(148.5,$linhaX, $codBarras,0,1);

        //Imprime o Código de barras
        $posY = $this->GetY() -1;
        $this->Cell(0,8,"",0,1);
        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 143, $posY , 60, 13);

        //Linha para separar um código de barras do outro
        $this->Cell(5,5," ",0,1);
        $this->Line(0,$this->GetY(),297,$this->GetY());

        $this->Line(135,0,135,$this->GetY());


        $this->SetX($posXIni);
        $this->SetY($posYIni);
        $this->SetFont('Arial', 'B', 13);
        $qtd = 0;
        foreach ($produtos as $keyId => $produto) {
            foreach ($produto as $keyGrade => $prod) {
                $this->Cell(1,6.5,self::SetStringByMaxWidth($keyId . " - ".$prod['descricao'], 131),0,1);
                $qtd = $qtd +1;
            }
        }

        while ($qtd <4) {
            $this->Cell(1,6.5,"",0,1);
            $qtd = $qtd +1;
        }
        /*
        $this->Cell(1,6.5,"Exemplo de produto 02",0,1);
        $this->Cell(1,6.5,"Exemplo de produto 01",0,1);$
        $this->Cell(1,6.5,"Exemplo de produto 03",0,1);
        $this->Cell(1,6.5,"Exemplo de produto 04",0,1);
        */
    }

    public function layoutModelo9 ($produto, $codBarras){

        if (count($produto) <= 0) {
            $dscProduto = "";
            $idProduto = "";
            $capacidadePicking = '';
            $descricaoEmbVol = "";
        } else {
            $idProduto = $produto['codProduto'];
            $dscProduto = utf8_decode($produto['descricao']);
            $capacidadePicking = $produto['capacidadePicking'];
            $descricaoEmbVol = $produto['descricaoEmbVol'];
        }

        if ($idProduto == "") {
            $idProduto = "";
        } else {
            $idProduto = $idProduto . " / " . $capacidadePicking.$descricaoEmbVol;
        }

        $this->SetFont('Arial', '', 13);
        $this->Cell(1,5,substr($dscProduto,0,25).'-',0,1);
        $this->Cell(1,3,substr($dscProduto,25,25),0,1);

        $this->SetFont('Arial', 'B', 13);

        /** Criar imagem para o endereco */
        $img = imagecreatefromjpeg(APPLICATION_PATH . '/../public/img/imagem.jpg');
        $cor = imagecolorallocate($img,0,0,0);
        $texto = $codBarras.'                  '.$idProduto;
        $fonte = APPLICATION_PATH . '/../public/img/arialbd.ttf';
        imagettftext($img,15,0,5,15,$cor,$fonte,$texto);
        imagejpeg($img,APPLICATION_PATH . '/../public/img/'.$codBarras.'.jpg',100);

        header('Content-type:image/jpeg');
        $this->Image(APPLICATION_PATH . '/../public/img/'.$codBarras.'.jpg' , 20, 12 , 50, 10);
        unlink(APPLICATION_PATH . '/../public/img/'.$codBarras.'.jpg');

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 22.5, 20 , 40, 10);

    }

    public function layoutModelo10($vetor)
    {
        $margin = 8;
        $this->InFooter = true;

        foreach ($vetor as $key => $itens) {
            $produto = $itens['produtos'];
            $endereco = $itens['codBarras'];
            $wDscProduto = 100;
            $fator = 29 * $key;
            $xDscCodBarrasProd = $wDscProduto;
            $codBarraProduto = $produto['codigoBarras'];
            $wDscEndereco = 60;
            $wCdoBarrasEnd = 45;

            $this->SetFont('Arial', '',11);
            $this->SetY($margin + $fator);
            $desc = "";
            if ((isset($produto['codProduto']) && !empty($produto['codProduto'])) &&
                (isset($produto['descricao']) && !empty($produto['descricao']))) {
                $desc = "$produto[codProduto] - $produto[descricao]";
            }

            $this->Cell($wDscProduto,4, self::SetStringByMaxWidth($desc, $wDscProduto),0,2);

            $this->SetXY($xDscCodBarrasProd + 4,$margin + $fator);
            $this->SetFont('Arial', 'B',12);
            $this->Cell(30,4, "EAN: $codBarraProduto",0,2);

            $this->SetY($margin + 8 + $fator);
            $this->SetFont('Arial', '',26);
            $this->Cell($wDscEndereco, 10, $endereco, 0,2);

            $posXRef = $wDscEndereco + $wCdoBarrasEnd + 2;
            $this->SetXY($posXRef, $margin + 8 + $fator);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(40,4,"REF: $produto[referencia]",0,2);

            $this->SetXY($posXRef, $margin + 12 + $fator);
            $this->Cell(40,4,self::SetStringByMaxWidth($produto['fabricante'],40),0,2);

            $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$endereco)) , $wDscEndereco, 15  + $fator, $wCdoBarrasEnd, 12);

        }
    }

    public function layoutModelo15 ($produto, $codBarras){
        $this->Cell(5,3,"",0,1);
        $arrEndereco = Endereco::separar($codBarras);
        $codBarras = implode('.',$arrEndereco);
        $this->SetX(5);
        $wRua = 19;
        $wPredio = 22;
        $wNivel = 18;
        $wApto = 23;
        $wTotal = $wRua + $wPredio + $wNivel + $wApto;

        $tamanhoCodigo = 20;

        $this->SetFont('Arial', 'B', $tamanhoCodigo);
        $this->Cell(30,3,"",0,1, 'C');
        $this->Cell(0,13,reset($produto)['codProduto'],0,1, 'C');
        $this->Cell(17,13,"",0,0);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(1.5,0," ",0,1);
        $this->SetX(17);
        $count = strlen(str_replace('.','',$codBarras));
        $fX = ($wTotal / $count) * 3.8;

        $this->SetFont('Arial', 'B', $fX);
        $this->Cell($wTotal,2.5,$codBarras,0,1);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $dadosLogisticos = $this->em->getRepository('wms:Produto\Embalagem')->findBy(array('codProduto' => reset($produto)['codProduto']), array('quantidade' => 'desc'));
        $dadosLogisticos = reset($dadosLogisticos);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 14, $this->GetY()+5, 90);
        $this->Cell(10,0," ",0,1);

        if (substr($arrEndereco['nivel'], -1) == 0) {
            $this->Image(APPLICATION_PATH . '/../data/seta1.png', 0, $this->GetY()-4,13,20);
        } else {
            $this->Image(APPLICATION_PATH . '/../data/seta2.png', 0, $this->GetY()-4, 13,20);
        }

        $this->SetY(2);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(10,27,"",0,1);
        $this->Cell($wRua,35,"ALT",0,0);
        $this->Cell($wPredio,35,"LARG",0,0);
        $this->Cell($wNivel,35,"PROF",0,0);
        $this->Cell($wApto,35,"PESO",0,0);
        $this->Cell($wApto,35,"CAP. (CX)",0,1);

        $this->SetY(27);

        $altura = '0,00';
        $largura = '0,00';
        $profundidade = '0,00';
        $peso = '0,00';
        $capacidade = '0,00';

        if (is_object($dadosLogisticos)) {
            $altura = $dadosLogisticos->getAltura();
            $largura = $dadosLogisticos->getLargura();
            $profundidade = $dadosLogisticos->getProfundidade();
            $peso = $dadosLogisticos->getPeso();
            $capacidade = $dadosLogisticos->getCapacidadePicking() / $dadosLogisticos->getQuantidade();
            $quantidade = $dadosLogisticos->getQuantidade();
        }

        $this->Cell($wRua,45,$altura,0,0);
        $this->Cell($wPredio,45,$largura,0,0);
        $this->Cell($wNivel,45,$profundidade,0,0);
        $this->Cell($wApto,45,$peso,0,0);
        $this->Cell($wApto+3,45,$capacidade,0,1);

    }

    //MODELO MOTO ARTE
    public function layoutModelo16 ($codBarras)
    {
        $this->Cell(5,3,"",0,1);
        $arrEndereco = Endereco::separar($codBarras);
        $codBarras = implode('.',$arrEndereco);
        $this->SetMargins(10,10);
        $this->SetXY(8,1);
        $wRua = 16;
        $wPredio = 22;
        $wNivel = 20;
        $wApto = 25;
        $wTotal = $wRua + $wPredio + $wNivel + $wApto;

        $this->InFooter = true;
        $this->Cell(17,13,"",0,0);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($wRua,13,utf8_decode("RUA"),0,0);
        $this->Cell($wPredio,13,utf8_decode("PREDIO"),0,0);
        $this->Cell($wNivel,13,utf8_decode("NIVEL"),0,0);
        $this->Cell($wApto,13,utf8_decode("APTO"),0,1);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0,0," ",0,1);
        $this->SetX(17);
        $count = strlen(str_replace('.','',$codBarras));
        $fX = ($wTotal / $count) * 4.12;
        $this->SetFont('Arial', 'B', $fX);
        $this->SetXY(24,15);
        $this->Cell($wTotal,8,$codBarras,0,1);

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$codBarras)) , 23, $this->GetY()+3 , 90);
        $this->Image(APPLICATION_PATH . '/../data/seta2.png', 6, $this->GetY(), 13,20);
        $this->InFooter = false;
    }

    public function layoutModelo17($codBarras){

        $enderecoEntity = $this->enderecoRepo->findOneBy(array('descricao' => $codBarras));

        $this->SetFont('Arial', 'B', 8);
        $this->InFooter = true;

        $format = Endereco::mascara(null, '0', Endereco::FORMATO_MATRIZ_ASSOC);
        $xRua = $this->GetStringWidth($format['rua']) + 8;
        $xPredio = $this->GetStringWidth($format['predio']) + 13;
        $xNivel = $this->GetStringWidth($format['nivel']) + 13;
        $xApto = $this->GetStringWidth($format['apartamento']) + 8;
        $maxW = ($xRua + $xPredio + $xNivel + $xApto);
        $center = ($this->GetPageWidth() / 2) - ($maxW / 2);

        $this->SetXY( $center,1);
        $this->Cell($xRua,13,utf8_decode("RUA"),0,0, 'C');
        $this->Cell($xPredio,13,utf8_decode("PREDIO"),0,0, 'C');
        $this->Cell($xNivel,13,utf8_decode("NIVEL"),0,0, 'C');
        $this->Cell($xApto,13,utf8_decode("APTO"),0,1, 'C');

        $this->SetXY( $center,12);
        $this->SetFont('Arial', 'B', 30);
        $this->Cell($maxW,5, $enderecoEntity->getDescricao(),0,1, 'C');

        $this->Image(@CodigoBarras::gerarNovo(str_replace(".","",$enderecoEntity->getDescricao())) , 10, 20 , 80, 15);
    }
}
