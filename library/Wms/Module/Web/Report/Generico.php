<?php

namespace Wms\Module\Web\Report;

use Core\Pdf;


class Generico extends Pdf {

    private $titulo;
    private $cabecalho;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',9);
        $this->Cell(20, 10, utf8_decode($this->titulo), 0, 1);

        $this->SetFont('Arial','B',8);
        foreach($this->cabecalho as $key => $linha )
        {
            $key = str_replace("_"," ",$key);
            $this->Cell($linha, 5, utf8_decode($key), 1,0);
        }
        $this->Cell(1, 5, "", 0,1);

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

    public function init(array $array = array(), $filename, $titulo) {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->titulo = $titulo;
        $this->SetFont('Arial', 'B', 8);

        $tamanho = $this->calculaTamanhoColunas($array);
        $this->cabecalho = $tamanho;

        $this->SetMargins(7, 0, 0);
        $this->AddPage();
        $this->SetFont('Arial','',8);
        foreach ($array as $linha)
        {
            foreach($linha as $key => $celula)
            {
                if($celula instanceof \DateTime){
                    $celula = $celula->format('d/m/Y H:i:s');
                }
                $align = "";
                if (is_int($celula)) $align = "C";
                if (is_float($celula)) $align = "R";

                $this->Cell($tamanho[$key], 5, utf8_decode($celula), 1,0,$align);
            }

            $this->Cell(20,5,'', 0,1 );
        }

        $this->Output($filename.'.pdf','D');
    }

    private function calculaTamanhoColunas ($array){
        $tamanho = array();

        $tamanhoTotal = 0;
        $countColumns = 1;
        foreach ($array as $linha)
        {
            $countColumns = count($linha);
            foreach($linha as $key => $celula)
            {
                if($celula instanceof \DateTime){
                    $celula = $celula->format('d/m/Y H:i:s');
                }
                $tamanhoCelula    = $this->GetStringWidth($celula);
                $tamanhoCabecalho = $this->GetStringWidth($key);

                if ($tamanhoCelula > $tamanhoCabecalho) {
                    $maiorTamanho = $tamanhoCelula + 5;
                } else {
                    $maiorTamanho = $tamanhoCabecalho + 5;
                }

                if (!(isset($tamanho[$key]))) {
                    $tamanho[$key] = $maiorTamanho;
                    $tamanhoTotal = $tamanhoTotal + $maiorTamanho;
                }

                if ($tamanho[$key] < $maiorTamanho){
                    $tamanhoTotal = $tamanhoTotal - $tamanho[$key] + $maiorTamanho;
                    $tamanho[$key] = $maiorTamanho;
                }
            }
        }

        $this->SetX(-7);
        $x2 = $this->GetX();
        $larguraDisponivel = $x2 - 7 - $tamanhoTotal;
        $larguraColunas = $larguraDisponivel/$countColumns;

        foreach ($tamanho as $key=> $coluna) {
            $tamanho[$key] = $coluna + $larguraColunas;
        }
        return $tamanho;
    }

}
