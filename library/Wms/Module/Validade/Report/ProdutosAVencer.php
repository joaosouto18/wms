<?php

namespace Wms\Module\Validade\Report;

use Core\Pdf;

class ProdutosAVencer extends Pdf
{
    private $pageW = 210;
    private $marginLeft = 7;
    private $prodListY = 20;
    private $lineH = 8;
    private $body;
    
    private function startPage($dataReferencia, $utilizaGrade)
    {
        $lineH = $this->lineH;
        $this->SetMargins($this->marginLeft,5);
        $this->AddPage();
        $this->SetFont('Arial', 'B', 15);
        $this->Cell($this->body, 15, utf8_decode("Produtos vencidos ou à vencer até $dataReferencia"),0,1,"C");

        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(255,255,255);
        $this->Cell(20, $lineH, utf8_decode("Produto") ,1 ,0 ,'' , true);
        $this->Cell(50, $lineH, utf8_decode('Descrição') ,1 ,0 ,'' , true);
        if ($utilizaGrade == 'S')
            $this->Cell(30, $lineH, utf8_decode("Grade") ,1 ,0 ,'' , true);
        $this->Cell(35, $lineH, utf8_decode("Linha Separação") ,1 ,0 ,'' , true);
        $this->Cell(40, $lineH, 'Fabricante' ,1 ,0 ,'' , true);
        $this->Cell(25, $lineH, utf8_decode('Endereço') ,1 ,0 ,'' , true);
        $this->Cell(25, $lineH, 'Picking' ,1 ,0 ,'' , true);
        $this->Cell(30, $lineH, 'Dt. validade' ,1 ,0 ,'' , true);
        $this->Cell(20, $lineH, 'D. Vencer' ,1 ,0 ,'' , true);
        $this->Cell(35, $lineH, 'Qtde' ,1 ,1,'', true);

    }

    private function addProdutoRow($produto, $i, $utilizaGrade)
    {
        $lineH = $this->lineH;

        $this->SetFont('Arial', '', 10);
        $this->Cell(20, $lineH, $produto['COD_PRODUTO'] ,1,0,'' , true);
        $this->Cell(50, $lineH, substr($produto['DESCRICAO'],0,20) ,1,0,'' , true);
        if ($utilizaGrade == 'S')
            $this->Cell(30, $lineH, $produto['GRADE'] ,1,0,'' , true);
        $this->Cell(35, $lineH, substr($produto['LINHA_SEPARACAO'],0,15),1,0,'' , true);
        $this->Cell(40, $lineH, substr($produto['FABRICANTE'],0,15),1,0,'' , true);
        $this->Cell(25, $lineH, $produto['ENDERECO'], 1, 0, '',true);
        $this->Cell(25, $lineH, $produto['PICKING'],1,0,'', true);
        $strg = (!empty($produto['VALIDADE']))? $produto['VALIDADE'] : 'Sem Registro';
        $this->Cell(30, $lineH, $strg,1,0,'', true);
        $this->Cell(20, $lineH, $produto['DIASVENCER'],1,0,'', true);
        $this->Cell(35, $lineH, $produto['QTD_MAIOR'],1,1,'', true);

    }

    public function Footer()
    {

        $this->SetFont('Arial','',9);
        $this->SetY(-20);
        $this->Cell(176, 15, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(20, 15, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');
    }

    public function generatePDF($produtos, $dataReferencia, $utilizaGrade)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $produtoEmbalagemRepository = $em->getRepository('wms:Produto\Embalagem');
        $this->body = $this->pageW - (2 * $this->marginLeft);

        self::startPage($dataReferencia, $utilizaGrade);
        $i = 0;

        foreach($produtos as $produto){
            if ($i > 19) {
                self::startPage($dataReferencia, $utilizaGrade);
                $i = 0;
            }
            self::addProdutoRow($produto, $i, $utilizaGrade);
            $i++;
        }

        self::Output('Produtos vencidos ou à vencer até '.$dataReferencia.'.pdf','I');
    }

}
