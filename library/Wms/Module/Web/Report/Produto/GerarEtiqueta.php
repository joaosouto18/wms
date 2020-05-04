<?php

namespace Wms\Module\Web\Report\Produto;

use Wms\Domain\Entity\Produto;
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

    public function init(array $nfParams = null,array $prodParams = null, $modelo, $target = Recebimento::TARGET_IMPRESSAO_ITEM, $importedFile = false, array $produtosRecebimento = null)
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

        $produtosEn = null;

        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        if ($tipo == "NF") {

            if (count($produtosRecebimento) >0) {
                $produtosEn = array();
                foreach ($produtosRecebimento as $produto) {
                    $codProduto = $produto['codProduto'];
                    $grade = $produto['grade'];
                    $emb = (isset($produto['emb'])) ? $produto['emb'] : null;
                    $result = $notaFiscalRepo->buscarProdutosImprimirCodigoBarras($idRecebimento, $codProduto, $grade, $emb);

                    /** @var Produto $prodEntity */
                    $prodEntity = $em->getRepository('wms:Produto')->find(["id" => $codProduto, "grade" => $grade]);
                    if ($prodEntity->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO)
                        $produtosEn[][] = $result[0];
                    else
                        $produtosEn[] = $result;
                }
            } else {
                $produtosEn = $notaFiscalRepo->buscarProdutosImprimirCodigoBarras($idRecebimento);
            }

        } else if ($tipo == "Produto") {
            /** @var ProdutoRepository $produtoRepo */
            $produtoRepo = $em->getRepository('wms:Produto');
            if (!$importedFile) {
                $produtosEn[] = $produtoRepo->buscarProdutosImprimirCodigoBarras($codProduto, $grade, $codProdutoEmbalagem);
            } else {
                foreach ($produtos as $produto) {
                    $dados = $produtoRepo->buscarProdutosImprimirCodigoBarras($produto, $grade);
                    foreach($dados as $dado) {
                        $produtosEn[] = $dado;
                    }
                }
            }
        }

        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        foreach ($produtosEn as $itens) {
            foreach ($itens as $produto) {
                $produto['dataValidade'] = "";
                if ($produto['validade'] == "S") {
                    $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto(null, $produto['codigoBarras'], $produto['idProduto'], $produto['grade']);
                    $produto['dataValidade'] = $getDataValidadeUltimoProduto['dataValidade'];
                }
                if ($target == Recebimento::TARGET_IMPRESSAO_PRODUTO) {
                    self::createEtiqueta($produto, $tipo, $modelo);
                } else {
                    $qtd = floor($produto['qtdItem']);
                    for ($i = 0; $i < $qtd; $i++) {
                        self::createEtiqueta($produto, $tipo, $modelo);
                    }
                }
            }
        }

        $this->Output("Etiqueta.pdf","D");
        exit;
    }

    public function etiquetaProdutosExpedicao(array $prodParams = null, $modelo, $target = Recebimento::TARGET_IMPRESSAO_ITEM)
    {
        extract($prodParams);
        $tipo = "Produto";

        $produtosEn = null;

        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        /** @var ProdutoRepository $produtoRepo */
        $produtoRepo = $em->getRepository('wms:Produto');

        foreach ($produtos as $i => $produto) {
            $dados = $produtoRepo->buscarProdutosImprimirCodigoBarras($produto['codProduto'], $produto['grade']);
            foreach($dados as $j => $dado) {
                $produtosEn[$i][$j] = $dado;
                $produtosEn[$i][$j]['qtdItem'] = $produto['qtdItem'];
            }
        }

        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
//        header('Content-type: application/pdf');

//        var_dump($produtosEn); exit;

        foreach ($produtosEn as $produto) {
            foreach ($produto as $item) {
                $item['dataValidade'] = "";
                if ($item['validade'] == "S") {
                    $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto(null, $item['codigoBarras'], $item['idProduto'], $item['grade']);
                    $item['dataValidade'] = $getDataValidadeUltimoProduto['dataValidade'];
                }
                for ($i = 0; $i < $item['qtdItem']; $i++) {
                    self::createEtiqueta($item, $tipo, $modelo);
                }
            }
        }

        $this->Output("Etiqueta-Produtos.pdf","D");
        exit;
    }

    public function etiquetaLote($lotes, $modelo){
        //geracao da etiqueta
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $this->SetMargins(6, 4, 0);
        foreach ($lotes as $lote) {
            switch ($modelo):
                default;
                    $this->layoutLoteDefault($lote);
                    break;
            endswitch;
        }
        $this->Output("Etiqueta-Lotes.pdf","D");
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
                $this->SetFont('Arial', 'B', 14);

                $this->layout4($produto, $tipo);
                break;
            case 5:
                $this->SetMargins(4, 5);
                $this->SetFont('Arial', 'B', 8);

                $this->layout5($produto, $tipo);
                break;
            case 6:
                $this->SetMargins(4, 5);
                $this->SetFont('Arial', 'B', 8);

                $this->layout6($produto);
                break;
            case "recebimento":
                $this->SetMargins(0, 1);
                $this->SetFont('Arial', 'B', 8);

                $this->layoutEtiquetaRecebimento($produto, $tipo);
                break;
            case 7:
                $this->SetMargins(6, 4, 0);
                $this->SetFont('Arial', 'B', 10);

                $this->layout7($produto);
                break;
            case 8:
                $this->SetMargins(4, 3, 0);
                $this->SetFont('Arial', 'B', 8);

                $this->layout8($produto, $tipo);
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
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " (".$produto['quantidade'].") - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
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
        if (strlen($produto['dscProduto']) <= 25) {
            $this->Cell(100,0,utf8_decode($produto['idProduto']) . " - " . utf8_decode($produto['dscProduto']) ,0,0);
        } else {
            $this->Cell(22,0,utf8_decode($produto['idProduto']) . " - ",0,0);
            $part1 = substr($produto['dscProduto'],0,25);
            $part2 = substr($produto['dscProduto'], 25, strlen($produto['dscProduto']));
            $this->Cell(80,0, utf8_decode($part1) ,0,0);
            if (!empty($part2)) {
                $this->Ln(6);
                $this->Cell(100, 0, utf8_decode($part2), 0, 0);
            }
        }

        //$this->Cell(100, 0, utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']), 0, 0);
        $this->Ln(6);
        $this->Cell(100, 0, 'Grade: ' . substr(utf8_decode($produto['grade']),0,25), 0, 0);
        $this->Ln(6);
        $this->Cell(100, 0, utf8_decode('Comercialização: ') . utf8_decode($produto['dscTipoComercializacao']));
        $this->Ln(6);
        $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fabricante: $produto[fabricante]"), 100), 0, 0);
        if ($tipo == "NF") {
//            $this->Ln(6);
//            $this->Cell(100, 0, self::SetStringByMaxWidth(utf8_decode("Fornecedor: $produto[fornecedor]"), 100), 0, 0);
        }

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(6);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(6);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }
        if ($produto['dataValidade'] != null) {
            $this->Ln(6);
            $dataValidade = new \DateTime($produto['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(100, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
        } else {
            $this->Ln(6);
            $dataImpressao = new \DateTime();
            $dataImpressao = $dataImpressao->format('d/m/Y');
            $this->Cell(100, 0, 'Data Impressao: ' . utf8_decode($dataImpressao), 0, 0);
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

    public function layout5($produto, $tipo)
    {
        $codigo = $produto['codigoBarras'];
        $this->AddPage();
        $this->MultiCell(100,2.7,utf8_decode($produto['idProduto']) . ' - ' . substr(utf8_decode($produto['dscProduto']), 0, 25),0,"L");
        $this->Ln(1.5);
        $this->MultiCell(100,2.7, substr(utf8_decode($produto['dscProduto']), 25, 200),0,"L");
        $this->Ln(5);

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem'])  . " (".$produto['quantidade'].") ", 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }


        $x        = 33;
        $y        = 35;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.45,15);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 11,$codigo);

    }

    public function layoutLoteDefault($lote)
    {
        $this->AddPage();
        $center = $this->w / 2;

        Barcode::fpdf($this,'000000', $center,22,0,'code128',array('code'=>$lote['DSC_LOTE']),0.7,12);
        $this->SetFont('Arial', 'B', 16);
        $this->Text( $center - ($this->GetStringWidth("Etiqueta de lote")/2), 12, "Etiqueta de lote");
        $this->SetFont('Arial', 'B', 22);
        $this->Text($center - ($this->GetStringWidth($lote['DSC_LOTE'])/2),38, $lote['DSC_LOTE']);
    }

    public function layout6($produto)
    {
        $codigo = $produto['codigoBarras'];
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell(110,2.7,utf8_decode($produto['idProduto']) . ' - ' . substr(utf8_decode($produto['dscProduto']), 0, 30),0,"C");
        $this->Ln(1.5);
        $this->MultiCell(110,2.7, substr(utf8_decode($produto['dscProduto']), 30, 200),0,"C");
        $this->Ln(5);

        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem'])  . " (".$produto['quantidade'].") ", 0, 0,'C');
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(3);
            $this->Cell(100, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }


        $x        = 60;
        $y        = 35;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.70,15);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 11,$codigo);

    }

    public function layoutEtiquetaRecebimento($produto, $tipo)
    {

        $codProduto = "(" . $produto['idProduto'] . ")";
        $notaFiscal = $produto['numNota'];
        $dscProduto = $produto['dscProduto'];
        $fornecedor = $produto['fornecedor'];
        $dataRecebimento = $produto['dataRecebimento']->format('d/m/Y');
        $this->AddPage();

        $codigo = $produto['codigoBarras'];

        $this->SetFont('Arial', 'B', 8);
        $this->MultiCell(50,3,utf8_decode($dscProduto),0,"C");
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 24);
        $this->Cell(50,2.7,utf8_decode($codProduto),0,1,"C");
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(50,5,"DATA: " . utf8_decode($dataRecebimento),0,1,"C");

        $this->Cell(50,2.7,"NF: " . utf8_decode($notaFiscal) .  "/" .  substr(utf8_decode($fornecedor),0,15) ,0,1,"C");
    }

    public function layout7($produto)
    {
        $codigo = $produto['codigoBarras'];

        $this->AddPage();
        $this->SetFont('Arial', 'B', 20);
        $this->MultiCell(90, 4, trim(utf8_decode($produto['idProduto'])),0,'C');
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 16);
        $this->MultiCell(90, 8, substr(trim(utf8_decode($produto['dscProduto'])),0,44),0,'C');
        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell(90, 0, trim('Embalagem: '. utf8_decode($produto['dscEmbalagem']) . ' ' . utf8_decode($produto['quantidade']). ' unidades'), 0, 0,'C');
        }

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

    public function layout8($produto, $tipo)
    {
        $codigo = $produto['codigoBarras'];

        $wCell = 90;
        $this->SetFontSize(12);
        $this->AddPage();
        $this->MultiCell($wCell,3.6,utf8_decode($produto['idProduto']) . ' - ' . utf8_decode($produto['dscProduto']),0,"L");

        $this->InFooter = true;
        $this->SetFontSize(10);
        if ($produto['idEmbalagem'] != null) {
            $this->Ln(3);
            $this->Cell($wCell, 0, 'Embalagem: ' . utf8_decode($produto['dscEmbalagem']) . " (".$produto['quantidade'].") - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }

        if ($produto['idVolume'] != null) {
            $this->Ln(3);
            $this->Cell($wCell, 0, 'Volume: ' . utf8_decode($produto['dscVolume']) . " - " . utf8_decode($produto['dscLinhaSeparacao']), 0, 0);
        }
        if ($produto['dataValidade'] != null) {
            $this->Ln(3);
            $dataValidade = new \DateTime($produto['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell($wCell, 0, 'Data Validade: ' . utf8_decode($dataValidade), 0, 0);
        }
        $this->SetFontSize(8);

        $x        = 35;
        $y        = 24;
        $height   = 8;
        $angle    = 0;
        $type     = 'code128';
        $black    = '000000';
        $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$codigo),0.5,10);
        $len = $this->GetStringWidth($data['hri']);

        $this->Text(($x-$height) + (($height - $len)/2) + 3,$y + 8,$codigo);

        $this->SetXY(70,11);
        $this->SetFontSize(10);
        $this->Cell(27, 20, 'PICKING', null,null,'C');

        $this->SetXY(70,15);
        $this->SetFontSize(12);
        $this->Cell(27, 20, $produto['picking'], null, null, 'C');

    }
}
