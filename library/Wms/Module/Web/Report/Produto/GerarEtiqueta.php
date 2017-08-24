<?php

namespace Wms\Module\Web\Report\Produto;

use Wms\Domain\Entity\ProdutoRepository;
use Wms\Util\Barcode\eFPDF,
    Wms\Util\Barcode\Barcode,
    Wms\Domain\Entity\NotaFiscalRepository,
    Wms\Domain\Entity\Recebimento;

/**
 * Description of GerarEtiqueta
 *
 * @author adriano uliana
 * modificado por Lucas Chinelate
 */
class GerarEtiqueta extends eFPDF
{

    public function init(array $nfParams = null,array $prodParams = null, $modelo, $target = "I")
    {
        $tipo = "";
        /*  PARA IMPRIMIR ETIQUETAS DE UM PRODUTO
            array ('codProduto' => CODIGO DO PRODUTO,
                   'grade' => GRADE)
        */
        if ($prodParams != NULL) {
            extract($prodParams);
            $tipo = "Produto";
        }

        /*  PARA IMPRIMIR ETIQUETAS DE UM RECEBIMENTO
            array ('idRecebimento' => ID DO RECEBIMENTO)
        */
        if ($nfParams != NULL) {
            extract($nfParams);
            $tipo = "NF";
        }

        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        if ($tipo == "NF") {
            $produtosEn = $notaFiscalRepo->buscarProdutosImprimirCodigoBarras($idRecebimento);
        } else if ($tipo == "Produto") {
            /** @var ProdutoRepository $produtoRepo */
            $produtoRepo = $em->getRepository('wms:Produto');
            $produtosEn = $produtoRepo->buscarProdutosImprimirCodigoBarras($codProduto, $grade);
            $target = Recebimento::TARGET_IMPRESSAO_PRODUTO;
        }

        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        foreach ($produtosEn as $produto) {
            $produto['dataValidade'] = "";
            if ($produto['validade'] == "S") {
                $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto(null, $produto['codigoBarras'], $produto['idProduto'], $produto['grade']);
                $produto['dataValidade'] = $getDataValidadeUltimoProduto['dataValidade'];
            }
            if ($target == Recebimento::TARGET_IMPRESSAO_PRODUTO) {
                self::createEtiqueta($produto, $tipo, $modelo);
            } else {
                for ($i = 0; $i < $produto['qtdItem']; $i++) {
                    self::createEtiqueta($produto, $tipo, $modelo);
                }
            }
        }
        $this->Output("Etiqueta.pdf","D");
        exit;
    }

    private function createEtiqueta($produto, $tipo, $modelo)
    {
        switch($modelo) {
            case 2:
                $this->SetMargins(6, 4, 0);
                $this->SetFont('Arial', 'B', 10);

                $this->layout2($produto, $tipo);
                break;
            case 3:
                $this->SetMargins(2, 2);
                $this->SetFont('Arial', 'B', 8);

                $this->layout3($produto, $tipo);
                break;
            case 4:
                $this->SetMargins(2, 2);
                $this->SetFont('Arial', 'B', 12);

                $this->layout4($produto, $tipo);
                break;
            default:
                $this->SetMargins(7, 5, 0);
                $this->SetFont('Arial', 'B', 8);

                $this->layout1($produto, $tipo);
                break;
        }
    }

    public function layout1($produto, $tipo)
    {
        $codigo = $produto['codigoBarras'];

        $this->AddPage();
        $this->MultiCell(100,2.7,utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']),0,"L");
        //$this->Cell(100, 0, utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 0, 0);
        $this->Ln(1.5);
        $this->Cell(100, 0, 'Grade: ' . utf8_decode($produto['grade']) . utf8_decode(' - Comercialização: ') . utf8_decode($produto['dscTipoComercializacao']), 0, 0);
        $this->Ln(3);
        $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fabricante: $produto[fabricante]"), 100), 0, 0);
        if ($tipo == "NF") {
            $this->Ln(3);
            $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fornecedor: $produto[fornecedor]"), 100), 0, 0);
        }

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }
        if ($produto['dataValidade'] != null) {
            $this->Ln(3);
            $dataValidade = new \DateTime($produto['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(100, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
        }

        $x        = 55;
        $y        = 28;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.5,10);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 8,$codigo);

    }


    public function layout2($produto, $tipo)
    {
	   $codigo = $produto['codigoBarras'];

        $this->AddPage();
		$this->SetFont('Arial', 'B', 12);
        $this->MultiCell(90, 4, utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 0);
        $this->SetFont('Arial', 'B', 10);
        $this->Ln(2);
        $this->Cell(100, 0, 'Grade: ' . utf8_decode($produto['grade']) . utf8_decode(' - Comercialização: ') . utf8_decode($produto['dscTipoComercializacao']), 0, 0);
        $this->Ln(4);
        $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fabricante: $produto[fabricante]"), 100), 0, 0);
        if ($tipo == "NF") {
            $this->Ln(3);
            $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fornecedor: $produto[fornecedor]"), 100), 0, 0);
        }

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(4);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(4);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) , 0, 0);
            $this->Ln(4);
            $this->Cell(100, 0, utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

//        if ($produto['dataValidade'] != null) {
//            $this->Ln(3);
//            $dataValidade = new \DateTime($produto['dataValidade']);
//            $dataValidade = $dataValidade->format('d/m/Y');
//            $this->Cell(100, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
//        }


        $x        = 55;
        $y        = 42;
        $height   = 12;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.5,12);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 12,$codigo);
    }

    public function layout3($produto, $tipo)
    {
        $codigo = $produto['codigoBarras'];

        $this->AddPage();
        $this->MultiCell(100,2.7,utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']),0,"L");
        //$this->Cell(100, 0, utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 0, 0);
        $this->Ln(1.5);
        $this->Cell(100, 0, 'Grade: ' . utf8_decode($produto['grade']) . utf8_decode(' - Comercialização: ') . utf8_decode($produto['dscTipoComercializacao']), 0, 0);
        $this->Ln(3);
        $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fabricante: $produto[fabricante]"), 100), 0, 0);
        if ($tipo == "NF") {
            $this->Ln(3);
            $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fornecedor: $produto[fornecedor]"), 100), 0, 0);
        }

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }
        if ($produto['dataValidade'] != null) {
            $this->Ln(3);
            $dataValidade = new \DateTime($produto['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(100, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
        }

        $x        = 40;
        $y        = 28;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.5,10);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 8,$codigo);

    }
    
    public function layout4($produto, $tipo)
    {
        $codigo = $produto['codigoBarras'];
        $this->AddPage();
        $this->Ln(3);
        $this->Cell(100,0, self::SetStringByMaxWidth(utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 100) ,0,0);
        //$this->Cell(100, 0, utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 0, 0);
        $this->Ln(10);
        $this->Cell(100, 0, 'Grade: ' . utf8_decode($produto['grade']) . utf8_decode(' - Comercialização: ') . utf8_decode($produto['dscTipoComercializacao']), 0, 0);
        $this->Ln(10);
        $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fabricante: $produto[fabricante]"), 100), 0, 0);
        if ($tipo == "NF") {
            $this->Ln(10);
            $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fornecedor: $produto[fornecedor]"), 100), 0, 0);
        }

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(10);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(10);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }
        if ($produto['dataValidade'] != null) {
            $this->Ln(10);
            $dataValidade = new \DateTime($produto['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(100, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
        }

        $x        = 55;
        $y        = 50;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.5,10);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 10,$codigo);

    }



}
