<?php
namespace Wms\Module\Web\Report;

use Core\Pdf;

class RelatoriosSimples extends Pdf
{
   private $header;

    /*public function __construct($arrayConsulta,$relatorio){
        $this->header=$arrayConsulta['header'];
        $this->init($arrayConsulta,$relatorio);
    }*/

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        if ( !empty( $this->header ) ){

            $this->Cell(20, 20, utf8_decode($this->header['titulo'] ), 0, 1);
            $this->SetFont('Arial', 'B', 8);

            $numCells=count($this->header['celulas']);
            $qtdCelulas=count($this->header['celulas']);
            for ($i=0; $i<$numCells; $i++){
                if ( $i==($qtdCelulas-1) )
                    $valor=1;
                else
                    $valor=0;
                $this->Cell($this->header['celulas'][$i]['tamanho'],  5, utf8_decode($this->header['celulas'][$i]['nome_celula'])  ,1, $valor);
            }
        }
    }

    private function setHeader($header){
        $this->header=$header;
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

    public function init($arrayConsulta,$relatorio)
    {
        if ( !empty($arrayConsulta['header']) )
	        $this->setHeader($arrayConsulta['header']);

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        $numRegistros=count($arrayConsulta);
        for ( $i=0; $i<$numRegistros; $i++ ){
            if ( !empty($arrayConsulta[$i]) && is_array($arrayConsulta[$i]) ){
                $numCampos=count($arrayConsulta[$i]);
                $qtdCampos=0;
                foreach ($arrayConsulta[$i] as $chv => $vlr) {
                    $this->SetFont('Arial', 'B', 8);

                    if ( $qtdCampos==($numCampos-1) )
                        $vlrCampo=1;
                    else
                        $vlrCampo=0;

                    $this->Cell($vlr['tamanho'], 5, $vlr['valor'] ,1, $vlrCampo);
                    $qtdCampos++;
                    //$this->Ln();
                }
            }
        }

        $nome=strtoupper($relatorio);
        $this->Output($nome.'.pdf','D');
    }
}
