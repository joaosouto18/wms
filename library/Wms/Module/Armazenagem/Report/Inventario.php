<?php
namespace Wms\Module\Armazenagem\Report;

use Core\Pdf;

class Inventario extends Pdf
{

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE INVENTARIO POR RUA" ), 0, 1);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(22,  5, utf8_decode("Endereço")  ,1, 0);
        $this->Cell(18,  5, utf8_decode("Código")   ,1, 0);
        $this->Cell(100, 5, utf8_decode("Descrição") ,1, 0);
		$this->Cell(43, 5, utf8_decode("Unitizador") ,1, 0);
        $this->Cell(12,  5, "Qtde" ,1, 1);
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

    public function init($saldo,$exibirEstoque = false)
    {
	
        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
//        $EstoqueRepository   = $em->getRepository('wms:Enderecamento\Estoque');


 //       $listaEstoque = $EstoqueRepository->getEstoqueByRua($params['inicioRua'], $params['fimRua'], $params['grandeza'], $params['picking'],$params['pulmao']);

        foreach ($saldo as $estoque) {

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(22, 5, $estoque['dscEndereco'] ,1, 0);
            $this->Cell(18, 5, $estoque['codProduto']      ,1, 0);
            $this->Cell(100, 5, $estoque['descricao'] ,1, 0);
			
			$qtd = "";
			if ($exibirEstoque == true) {
				$qtd = $estoque['qtd'];
			}

			$this->Cell(43, 5, $estoque['unitizador'] ,1, 0);
            $this->Cell(12, 5, $qtd ,1, 1);

            //$this->Ln();
        }
        $this->Output('Inventario-Por-Rua.pdf','D');
    }
}
