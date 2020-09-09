<?php

namespace Wms\Module\Expedicao\Printer;

use Doctrine\ORM\EntityManager;
use Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\ExpedicaoRepository;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Math;
use Wms\Util\Barcode\Barcode;
use Wms\Util\Barcode\eFPDF;

class MapaSeparacao extends eFPDF {

    private $idMapa;
    private $idExpedicao;
    private $quebrasEtiqueta;
    private $pesoTotal, $cubagemTotal, $mapa, $imgCodBarras, $total, $pesoCarga, $cubagemCarga;
    private $itinerarios;

    /** @var $em EntityManager */
    private $em;

    private $countPages;
    private $currentPage;

    /** @var Produto\EmbalagemRepository $embalagemRepo */
    private $embalagemRepo;
    /** @var ExpedicaoRepository $expedicaoRepo */
    private $expedicaoRepo;
    /** @var Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdRepo */
    private $mapaSeparacaoProdRepo;
    /** @var Expedicao\MapaSeparacaoQuebraRepository $mapaQuebraRepo */
    private $mapaQuebraRepo;
    /** @var Expedicao\CargaRepository $cargaRepo */
    private $cargaRepo;
    /** @var Expedicao\PedidoRepository $pedidoRepo */
    private $pedidoRepo;
    /** @var Produto\PesoRepository $pesoProdutoRepo */
    private $pesoProdutoRepo;

    protected $chaveCargas;
    protected $cargasSelecionadas;


    public function layoutMapa($expedicao, $modelo, $codBarras = null, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $idBox = null)
    {

        $this->em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $this->mapaSeparacaoProdRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $this->mapaQuebraRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
        $this->embalagemRepo = $this->em->getRepository('wms:Produto\Embalagem');
        $this->pedidoRepo = $this->em->getRepository('wms:Expedicao\Pedido');
        $this->cargaRepo = $this->em->getRepository('wms:Expedicao\Carga');
        $this->pesoProdutoRepo = $this->em->getRepository('wms:Produto\Peso');

        $this->itinerarios = $this->expedicaoRepo->getItinerariosByExpedicao($expedicao);
        $this->idExpedicao = $expedicao;

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        if ($codBarras == null) {
            $mapaSeparacao = $this->em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('expedicao' => $expedicao, 'codStatus' => $status));
        } else {
            $mapaSeparacao = $this->em->getRepository('wms:Expedicao\MapaSeparacao')->getMapaSeparacaoById($codBarras);
        }

        $qtdProdPorMapa = array();
        $peso = 0;
        $cubagem = 0;
        foreach ($mapaSeparacao as $mapa) {
            $qtdProdutos = $this->mapaSeparacaoProdRepo->findBy(array('mapaSeparacao' => $mapa->getId()));
            $qtdProdPorMapa[] = array('idMapa' => $mapa->getId(), 'qtdProd' => count($qtdProdutos));

            foreach ($qtdProdutos as $prod) {
                $qtdEmbalagem = $prod->getQtdEmbalagem();
                $quantidade = $prod->getQtdSeparar();
                $codProduto = $prod->getCodProduto();
                $grade = $prod->getDscGrade();

                $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $codProduto, 'grade' => $grade));

                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $peso    += ($pesoProduto->getPeso() * $quantidade * $qtdEmbalagem);
                    $cubagem += ($pesoProduto->getCubagem() * $quantidade * $qtdEmbalagem);
                }


            }
        }

        $this->pesoCarga = $peso;
        $this->cubagemCarga = $cubagem;

        if (($modelo == 11) || ($modelo == 5) || $modelo == 14){
            if ($modelo == 11) $limitPg = 30;
            if ($modelo == 5)  $limitPg = 42;
            if ($modelo == 14) $limitPg = 42;

            $qtdPag = 0;
            foreach ($qtdProdPorMapa as $mapa) {
                $qtdPag += ceil($mapa['qtdProd'] / $limitPg);
            }
        } else {
            $qtdPag = count($mapaSeparacao);
        }
        $this->countPages = $qtdPag;


        /** @var Parametro $param */
        $param = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => "UTILIZA_GRADE"));
        if (!empty($param)) {
            $usaGrade = $param->getValor();
        } else {
            $usaGrade = 'N';
        }

        $dscBox = null;
        if (!is_null($idBox)) {
            $boxEntity = $this->em->getReference('wms:Deposito\Box',$idBox);
            $dscBox = $boxEntity->getDescricao();
        }

        $txtCarga = 'CARGA';
        $cargasSelecionadas = $this->getCargasSelecionadas();
        if (empty($cargasSelecionadas)) {
            $cargas = $this->expedicaoRepo->getCodCargasExterno($this->idExpedicao);
            $stringCargas = null;
            foreach ($cargas as $key => $carga) {
                unset($carga['sequencia']);
                if ($key >= 1) {
                    $stringCargas .= ',';
                }
                $stringCargas .= implode(',', $carga);
                $txtCarga = (count($cargas) > 1) ? 'CARGAS' : 'CARGA';
            }
        } else {
            if (is_array($cargasSelecionadas)) {
                $stringCargas = implode(',', $cargasSelecionadas);
                if (count($cargasSelecionadas) > 1)
                    $txtCarga = 'CARGAS';
            } else {
                $stringCargas = $cargasSelecionadas;
            }
        }

        $count = 0;
        $this->currentPage = 0;
        /** @var Expedicao\MapaSeparacao $mapa */
        foreach ($mapaSeparacao as $mapa) {

            $produtos = [];
            $quebraConsolidado = null;
            $quebraFracionavel = null;

            if (!in_array($modelo,[6])) {
                $produtos = $this->mapaSeparacaoProdRepo->getMapaProduto($mapa->getId());
                $quebraConsolidado = $this->mapaQuebraRepo->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
                $quebraFracionavel = $this->mapaQuebraRepo->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_UNID_FRACIONAVEL));
            }
            if (!empty($quebraFracionavel)) {
                $this->layoutMapaFracionaveis($mapa, $produtos, $usaGrade, $dscBox, ['txt' => $txtCarga, 'str' => $stringCargas]);
            } else {
                switch ($modelo) {
                    case 2:
                        $this->layoutModelo2($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 3:
                        $this->layoutModelo3($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 4:
                        $this->layoutModelo4($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 5:
                        $this->layoutModelo5($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 6:
                        if(in_array($count, [0,7])){
                            $this->AddPage();
                            $this->SetFont('Arial', 'B', 10);
                            $this->Cell(200, 3, utf8_decode("MAPA DE CONFERENCIA - EXPEDIÇÃO " . $expedicao), 0, 1, "C");
                            $count = 0;
                        }
                        $this->layoutModelo6($mapa, ['txt' => $txtCarga, 'str' => $stringCargas], $dscBox);
                        $count++;
                        break;
                    case 7:
                        $this->layoutModelo7($mapa, $produtos, $usaGrade, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 8:
                        $this->layoutModelo8($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 9:
                        $this->layoutModelo9($mapa, $produtos, $usaGrade, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 10:
                        $this->layoutModelo10($mapa, $produtos, $usaGrade, !empty($quebraConsolidado), $dscBox, ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 11:
                        $this->layoutModelo11($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 12:
                        $this->layoutModelo12($mapa, $produtos, $usaGrade, !empty($quebraConsolidado), $dscBox, ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 13:
                        $this->layoutModelo13($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    case 14:
                        $this->layoutModelo14($mapa, $produtos, !empty($quebraConsolidado), ['txt' => $txtCarga, 'str' => $stringCargas]);
                        break;
                    default:
                        $this->layoutModelo1($mapa, $produtos, $usaGrade, !empty($quebraConsolidado), $dscBox, ['txt' => $txtCarga, 'str' => $stringCargas]);
                }
            }

            if ($mapa->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
                $this->em->persist($mapa);
            }
        }

        if ($modelo == 4) {
            $produtos = $this->mapaSeparacaoProdRepo->getMapaProdutoByExpedicao($expedicao);

            if (!empty($produtos)) {
                $this->AddPage();
//Select Arial bold 8
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 10, utf8_decode("RELATÓRIO DE CODIGO DE BARRAS DE PRODUTOS DA EXPEDIÇÃO " . $this->idExpedicao), 0, 1);

                $x = 170;
                $y = 30;
                $count = 1;
                foreach ($produtos as $produto) {
                    $height = 8;
                    $angle = 0;
                    $type = 'code128';
                    $black = '000000';

                    if ($count > 12) {
                        $this->AddPage();
                        $count = 1;
                        $y = 30;
                    }

                    $this->SetFont('Arial', '', 10);
                    $this->Cell(15, 20, $produto['id'], 0, 0);
                    $this->Cell(90, 20, substr($produto['descricao'], 0, 40), 0, 0);
                    $this->Cell(90, 20, $produto['unidadeMedida'], 0, 1);

                    $data = Barcode::fpdf($this, $black, $x, $y, $angle, $type, array('code' => $produto['codigoBarras']), 0.5, 10);
                    $len = $this->GetStringWidth($data['hri']);
                    $this->Text(($x - $height) + (($height - $len) / 2) + 3, $y + 8, $produto['codigoBarras']);
                    $y = $y + 20;
                    $count++;
                }
            }
        }

        $this->Output('Mapa Separação-' . $expedicao . '.pdf', 'D');

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @param $mapa Expedicao\MapaSeparacao
     * @param $produtos Expedicao\MapaSeparacaoProduto[]
     * @param $usaGrade
     * @param $tipoQuebra
     * @param $dscBox
     * @param $arrDataCargas
     */
    private function layoutModelo1($mapa, $produtos, $usaGrade, $tipoQuebra, $dscBox, $arrDataCargas)
    {
        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->AddPage();

        $codCargaExterno = explode(',', $arrDataCargas['str']);
        $cargaEn = $this->cargaRepo->findOneBy(array('codCargaExterno' => array($codCargaExterno[0])));
        $pedidosEntities = $this->pedidoRepo->findBy(array('carga' => $cargaEn->getId()), array('linhaEntrega' => 'ASC'));
        $itinerarios = array();
        $ultimaLinha = '';
        foreach ($pedidosEntities as $key => $pedidosEntity) {

            if ($ultimaLinha != $pedidosEntity->getLinhaEntrega()) {
                $itinerarios[] = $pedidosEntity->getLinhaEntrega();
            }

            $ultimaLinha = $pedidosEntity->getLinhaEntrega();
        }
        $idCliente = $pedidosEntities[0]->getPessoa()->getId();

        $pessoaEntity = $this->em->getRepository('wms:Pessoa')->find($idCliente);
        $nomeFantasia = $pessoaEntity->getNome();

        $linhaSeparacao = implode(', ',$itinerarios);

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(35, 5.5, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 14);
        $this->Cell(4, 5.5, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str] - $linhaSeparacao", 0, 1);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(35, 5.5, utf8_decode("CLIENTE: "), 0, 0);
        $this->SetFont('Arial', null, 14);
        $this->Cell(20, 5.5, utf8_decode($nomeFantasia), 0, 1);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(35, 5.5, utf8_decode("PLACA/BOX: "), 0, 0);
        $this->SetFont('Arial', null, 14);
        $this->Cell(20, 5.5, $cargaEn->getPlacaCarga().'/'.$dscBox, 0, 1);
        $this->Cell(20, 4, "", 0, 1);

        $this->SetFont('Arial', 'B', 9);

        if ($usaGrade === 'N') {
            if ($tipoQuebra == true) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(99, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Embalagem"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(98, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(31, 5, utf8_decode("Emb"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Qtd."), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }
        } else {
            if ($tipoQuebra == true) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                $this->Cell(78, 5, utf8_decode("Produto"), 1, 0); //20
                $this->Cell(18, 5, utf8_decode("Emb"), 1, 0); //10
                $this->Cell(18, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
//195
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                $this->Cell(93, 5, utf8_decode("Produto"), 1, 0); //10
                $this->Cell(18, 5, utf8_decode("Emb"), 1, 0); //15
                $this->Cell(18, 5, utf8_decode("Qtd"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }
        }

        $addNewRow = function ($usaGrade, $tipoQuebra, $dscEndereco, $codProduto, $grade, $descricao, $embalagem, $quantidade, $caixas) {
            if ($usaGrade === "S") {
                if ($tipoQuebra == true) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(20, 6, $grade, 0, 0);
                    $this->Cell(78, 6, $descricao, 0, 0);
                    $this->Cell(18, 6, $embalagem, 0, 0);
                    $this->Cell(18, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(20, 6, $grade, 0, 0);
                    $this->Cell(93, 6, $descricao, 0, 0);
                    $this->Cell(18, 6, $embalagem, 0, 0);
                    $this->Cell(18, 6, $quantidade, 0, 1, 'C');
                }
            } else {
                if ($tipoQuebra == true) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(99, 6, $descricao, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 0, 'C');
                    $this->Cell(15, 6, $caixas, 0, 0, 'C');
                    $this->Cell(20, 6, $embalagem, 0, 1, 'C');
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(98, 6, $descricao, 0, 0);
                    $this->Cell(31, 6, $embalagem, 0, 0);
                    $this->Cell(20, 6, $quantidade, 0, 1, 'C');
                }
            }
        };

        $checkBreakLine = function ($str, $sizeCell, $arr) use (&$checkBreakLine){
            // Máximo da string que cabe na célula
            $strTrimmed = $this->SetStringByMaxWidth($str, $sizeCell, false);
            // qtd de caracteres da string recortada
            $lStr = strlen($strTrimmed);

            if ($lStr < strlen($str)) {
                $arr[] = $strTrimmed;
                $arr = $checkBreakLine(substr($str, $lStr, strlen($str)), $sizeCell, $arr);
            } else {
                $arr[] = $str;
            }

            return $arr;
        };

        $pesoTotal = 0.0;
        $cubagemTotal = 0.0;
        /** @var Expedicao\MapaSeparacaoProduto $produto */
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $dscEndereco = "";
            $embalagem = $produto->getProdutoEmbalagem();
            $codProduto = $produto->getCodProduto();
            $grade = $produto->getDscGrade();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());

            if ($produto->getProdutoVolume() == null) {
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $endereco = $produto->getDepositoEndereco();
            }else{
                $embalagem = $produto->getProdutoVolume()->getDescricao();
                $endereco = $produto->getProdutoVolume()->getEndereco();
            }
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            $this->SetFont('Arial', null, 9);

            $rowsGrade[0] = $grade;
            if ($usaGrade)
                $rowsGrade = $checkBreakLine($grade, 20, []);

            if ($usaGrade === "S") {
                if ($tipoQuebra == true) {
                    $wDesc = 78;
                } else {
                    $wDesc = 93;
                }
            } else {
                if ($tipoQuebra == true) {
                    $wDesc = 99;
                } else {
                    $wDesc = 98;
                }
            }

            $rowsDesc = $checkBreakLine($descricao, $wDesc, []);

            $nRows = (count($rowsGrade) < count($rowsDesc)) ? count($rowsDesc) : count($rowsGrade);
            for($i = 0; $i < $nRows; $i++) {
                if ($i === 0) $addNewRow($usaGrade, $tipoQuebra, $dscEndereco, $codProduto, $rowsGrade[0], $rowsDesc[0], $embalagem, $quantidade, $caixas);
                else {
                    $strGrade = (!empty($rowsGrade[$i])) ? $rowsGrade[$i] : "";
                    $strDesc = (!empty($rowsDesc[$i])) ? $rowsDesc[$i] : "";
                    $addNewRow($usaGrade, $tipoQuebra, "", "", $strGrade, $strDesc, "", "", "");
                }
            }

            $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->Cell(20, 1, "", 0, 1);
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, 150, 280, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $mapa->getId()), 0, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(15, 6, "PLACA: ", 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, $cargaEn->getPlacaCarga(). ' - ' . substr($linhaSeparacao,0,30), 0, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(23, 6, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, utf8_decode($this->idExpedicao), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 6, utf8_decode("$arrDataCargas[txt]: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 4, 6, $arrDataCargas['str'], 0, 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 0);
        $this->Cell($wPage * 2, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->InFooter = false;

        $observacoes = $this->pedidoRepo->getObservacaoPedido($this->idExpedicao);
        if (!is_null($observacoes)) {
            $this->AddPage();
            $carga = 0;
            $cliente = 0;
            $pedido = 0;
            foreach ($observacoes as $observacao) {

                if ($carga != $observacao['codCarga']) {
                    $this->SetFont('Arial', 'B', 17);
                    $this->Cell(20,12,"Carga: ",0,0);

                    $this->SetFont('Arial', '', 17);
                    $this->Cell(20,12,$observacao['codCargaExterno'],0,1);
                }
                if ($cliente != $observacao['codCliente']) {
                    $this->SetFont('Arial', 'B', 15);
                    $this->Cell(20,12,"Cliente: ",0,0);

                    $this->SetFont('Arial', '', 15);
                    $this->Cell(20,12,utf8_decode("$observacao[codClienteExterno] - $observacao[nome]"),0,1);
                }

                if ($pedido != $observacao['codPedido']) {
                    $this->SetFont('Arial', 'B', 15);
                    $this->Cell(20,8,"Pedido: ",0,0);

                    $this->SetFont('Arial', '', 15);
                    $this->Cell(20,8, $observacao['codExterno'],0,1);
                }

                $this->SetFont('Arial', 'B', 12);
                $this->Cell(20,5,"Obs.: ",0,0);

                $this->SetFont('Arial', '', 12);
                $this->MultiCell(160, 5, utf8_decode($observacao['observacao']),0,'L');
                $this->Line(0,$this->getY(),200,$this->getY());
                $this->Ln(7);

                $carga   = $observacao['codCarga'];
                $cliente = $observacao['codCliente'];
                $pedido  = $observacao['codPedido'];

            }
        }
    }

    private function layoutModelo2($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->idMapa = $mapa->getId();
        $pesoTotal = 0.0;
        $cubagemTotal = 0.0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

//Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, '', 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

        $this->Image($imgCodBarras, 150, 3, 50);
        $this->Cell(20, 4, "", 0, 1);
        $this->SetFont('Arial', 'B', 8);

        if ($tipoQuebra) {
            $this->Cell(16, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(13, 5, utf8_decode("Cod."), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(25, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(15, 5, utf8_decode("Refer."), 1, 0);
            $this->Cell(15, 5, utf8_decode("Emb."), 1, 0);
            $this->Cell(12, 5, utf8_decode("Qtd."), 1, 0);
            $this->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
        } else {
            $this->Cell(16, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(13, 5, utf8_decode("Cod."), 1, 0);
            $this->Cell(100, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(25, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(15, 5, utf8_decode("Refer."), 1, 0);
            $this->Cell(12, 5, utf8_decode("Emb."), 1, 0);
            $this->Cell(12, 5, utf8_decode("Qtd."), 1, 1);
        }

        $this->Cell(20, 1, "", 0, 1);

        $total = 0;
        foreach ($produtos as $produto) {
            /** @var Expedicao\MapaSeparacaoProduto $produto */
            $produto = reset($produto);
            $this->SetFont('Arial', null, 8);
            $embalagemEn = $this->embalagemRepo->findOneBy(array('codProduto' => $produto->getCodProduto(), 'grade' => $produto->getDscGrade(), 'isPadrao' => 'S'));

            $codigoBarras = '';
            $embalagem = '';

            if (!empty($embalagemEn)) {
                $embalagem = $produto->getProdutoEmbalagem();
                if ($embalagem->getQuantidade() == $embalagemEn->getQuantidade()) {
                    $embalagem = $embalagemEn->getDescricao() . "(" . $embalagemEn->getQuantidade() . ")";
                    $codigoBarras = $embalagemEn->getCodigoBarras();
                } else {
                    $codigoBarras = $embalagem->getCodigoBarras();
                    $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                }
            }

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $referencia = $produto->getProduto()->getReferencia();
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";


            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            if ($tipoQuebra) {
                $this->Cell(16, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(85, 4, substr($descricao, 0, 45), 0, 0);
                $this->Cell(25, 4, $codigoBarras, 0, 0);
                $this->Cell(15, 4, $referencia, 0, 0);
                $this->Cell(10, 4, $embalagem, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 0);
                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->Cell(16, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(100, 4, substr($descricao, 0, 57), 0, 0);
                $this->Cell(25, 4, $codigoBarras, 0, 0);
                $this->Cell(15, 4, $referencia, 0, 0);
                $this->Cell(10, 4, $embalagem, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1, 'C');
            }
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }

//FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell($wPage * 2.5, 6, utf8_decode("EXPEDIÇÃO: " . $this->idExpedicao), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

        $this->Image($this->imgCodBarras, 143, 280, 50);
        $this->InFooter = false;
    }

    private function layoutModelo3($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

//Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, '', 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

        $this->Image($imgCodBarras, 150, 3, 50);
        $this->Cell(20, 4, "", 0, 1);
        $this->SetFont('Arial', 'B', 8);

        if ($tipoQuebra) {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(17, 5, utf8_decode("Cod.Prod."), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(15, 5, utf8_decode("Grade."), 1, 0);
            $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Qtd."), 1, 0);
            $this->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
        } else {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(40, 5, utf8_decode("Grade."), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Quant."), 1, 1);
        }

        $this->Cell(20, 1, "", 0, 1);

        $total = 0;
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $this->SetFont('Arial', null, 8);
            $embalagemEn = $this->embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
            $volumeEn = $produto->getProdutoVolume();

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";
            $codigoBarras = '';
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $codigoBarras = $embalagemEn->getCodigoBarras();
            } elseif (isset($volumeEn) && !empty($volumeEn)) {
                $codigoBarras = $volumeEn->getDescricao();
            }

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            if ($tipoQuebra) {
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(17, 4, $codProduto, 0, 0);
                $this->Cell(85, 4, substr($descricao, 0, 45), 0, 0);
                $this->Cell(15, 4, $produto->getProduto()->getGrade(), 0, 0);
                $this->Cell(30, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 0);
                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(20, 4, $codProduto, 0, 0);
                $this->Cell(85, 4, substr($descricao, 0, 57), 0, 0);
                $this->Cell(40, 4, substr($produto->getProduto()->getGrade(), 0, 20), 0, 0);
                $this->Cell(20, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1, 'C');
            }
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }

//FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell($wPage * 4, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->Cell($wPage * 4, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
        $this->Cell($wPage * 4, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

        $this->Image($this->imgCodBarras, 143, 280, 50);
        $this->InFooter = false;
    }

    private function layoutModelo4($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $codCargaExterno = explode(',', $arrDataCargas['str']);
        $cargaEn = $this->cargaRepo->findOneBy(array('codCargaExterno' => array($codCargaExterno[0])));
        $pedidoEn = $this->pedidoRepo->findOneBy(array('carga' => $cargaEn->getId()));
        $linhaSeparacao = '';
        if (isset($pedidoEn) && !empty($pedidoEn))
            $linhaSeparacao = $pedidoEn->getItinerario()->getDescricao();

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

        $this->Cell(20, 1, "", 0, 1);

        $total = 0;
        $ruaAnterior = 99999;
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $this->SetFont('Arial', null, 8);

            $embalagemEn = $produto->getProdutoEmbalagem();
            $rua = null;
            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $quantidade = $produto->getQtdSeparar();
            $caixaInicio = $produto->getNumCaixaInicio();
            $caixaFim = $produto->getNumCaixaFim();

            $caixas = $caixaInicio . ' - ' . $caixaFim;
            $dscEndereco = '';
            $codigoBarras = '';
            if ($endereco != null) {
                $dscEndereco = $endereco->getDescricao();
                $rua = $endereco->getRua();
            }
            if (isset($embalagemEn) && !empty($embalagemEn))
                $codigoBarras = $embalagemEn->getCodigoBarras();

            $embalagem = $embalagemEn->getDescricao() . ' (' . $embalagemEn->getQuantidade() . ')';
            $pesoTotal += Math::multiplicar(str_replace(',', '.', $embalagemEn->getPeso()), str_replace(',', '.', $quantidade));
            $cubagemTotal += Math::multiplicar(str_replace(',', '.', $embalagemEn->getCubagem()), str_replace(',', '.', $quantidade));

            if ($ruaAnterior != $rua) {
                $this->Cell(20, 7, "", 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(24, 2, utf8_decode("EXPEDIÇÃO: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 2, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);

                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 2, '', 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 2, utf8_decode("QUEBRAS: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(20, 2, utf8_decode($this->quebrasEtiqueta), 0, 1);

                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 2, '', 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->SetFont('Arial', null, 10);

                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 2, '', 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 2, utf8_decode("RUA: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(20, 2, utf8_decode($rua), 0, 1);

                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 2, '', 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 2, utf8_decode("PLACA: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(20, 2, utf8_decode($cargaEn->getPlacaCarga()), 0, 1);

                $this->Cell(20, 4, "", 0, 1);
                $this->SetFont('Arial', 'B', 8);

                if ($tipoQuebra) {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(17, 5, utf8_decode("Cod.Prod."), 1, 0);
                    $this->Cell(80, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
                    $this->Cell(12, 5, utf8_decode("Qtd."), 1, 0);
                    $this->Cell(15, 5, utf8_decode("Emb.:"), 1, 0);
                    $this->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
                } else {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
                    $this->Cell(15, 5, utf8_decode("Quant."), 1, 0);
                    $this->Cell(18, 5, utf8_decode("Emb.:"), 1, 1);
                }

                $this->Cell(20, 1, "", 0, 1);
            }

            if ($tipoQuebra) {
                $this->SetFont('Arial', "", 8);
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(17, 4, $codProduto, 0, 0);
                $this->Cell(80, 4, substr($descricao, 0, 45), 0, 0);
                $this->Cell(30, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(12, 4, $quantidade, 0, 0);
                $this->SetFont('Arial', '', 10);
                $this->Cell(16, 4, $embalagem, 0, 0);
                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->SetFont('Arial', "", 8);
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(20, 4, $codProduto, 0, 0);
                $this->Cell(90, 4, substr($descricao, 0, 57), 0, 0);
                $this->Cell(25, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(20, 4, $quantidade, 0, 0, 'C');
                $this->SetFont('Arial', '', 10);
                $this->Cell(12, 4, $embalagem, 0, 1);
            }
            $ruaAnterior = $rua;
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }

//FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $this->InFooter = true;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(16 * 4, 6, utf8_decode("QUEBRAS: " . substr($this->quebrasEtiqueta, 0, 23)), 0, 0);
        $this->Cell(14 * 4, 6, utf8_decode("EXPEDICAO " . $this->idExpedicao), 0, 0);
        $this->Cell(10 * 4, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);
        $this->Cell(16 * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell(10 * 4, 6, utf8_decode("CARREGAMENTO " . $arrDataCargas['str']), 0, 1);
        $this->Cell(16 * 4, 6, utf8_decode("ROTA: " . $linhaSeparacao), 0, 0);
        $this->Cell(10 * 4, 6, utf8_decode('PESO TOTAL ' . number_format($this->pesoTotal, 3, ',', '') . 'kg'), 0, 1);
        $this->Image($this->imgCodBarras, 143, 280, 50);

        $this->InFooter = false;

    }

    private function layoutModelo5($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());
        /**
         * Cria cabeçalho
         */
        //Select Arial bold 8
        $this->Cell(20, 1, "", 0, 1);
        $total = 0;
        $contadorPg = 0;
        $limitPg = 42;
        $totalPg = ceil(count($produtos) / $limitPg);
        $pgAtual = 1;
        $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, '1 de ' . $totalPg);
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $contadorPg++;
            if ($contadorPg == $limitPg) {
                $contadorPg = 0;
                $pgAtual++;
                /**
                 * Cria rodape
                 */
                $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total);
                /**
                 * Cria cabeçalho
                 */
                $paginas = $pgAtual . ' de ' . $totalPg;
                $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, $paginas);
            }

            $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

            $codigoBarras = '';
            $embalagem = '';

            $embalagemEn = $produto->getProdutoEmbalagem();
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $codigoBarras = '...' . substr($embalagemEn->getCodigoBarras(), -5);
                $embalagem = $embalagemEn->getDescricao() . ' (' . $embalagemEn->getQuantidade() . ')';
            }

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $referencia = $produto->getProduto()->getReferencia();
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";

            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if (isset($pesoProduto) && !empty($pesoProduto)) {
                $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                $cubagemTotal += $pesoProduto->getCubagem() * $quantidade;
            }

            $addNewRow = function ($tipoQuebra, $dscEndereco, $codProduto, $descricao, $codigoBarras, $referencia, $embalagem, $quantidade, $caixas) {
                $this->SetFont('Arial', null, 8);
                if ($tipoQuebra) {
                    $this->Cell(21, 4, $dscEndereco, 0, 0);
                    $this->Cell(13, 4, $codProduto, 0, 0);
                    $this->Cell(90, 4, $descricao, 0, 0);
                    $this->Cell(20, 4, $codigoBarras, 0, 0);
                    $this->Cell(25, 4, $referencia, 0, 0);
                    $this->Cell(10, 4, $embalagem, 0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade, 0, 0);
                    $this->Cell(15, 4, $caixas, 0, 1, 'C');
                } else {
                    $this->Cell(21, 4, $dscEndereco, 0, 0);
                    $this->Cell(13, 4, $codProduto, 0, 0);
                    $this->Cell(90, 4, $descricao, 0, 0);
                    $this->Cell(20, 4, $codigoBarras, 0, 0);
                    $this->Cell(25, 4, $referencia, 0, 0);
                    $this->Cell(10, 4, $embalagem, 0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade, 0, 1, 'C');
                }
            };

            $checkBreakLine = function ($str, $sizeCell, $arr) use (&$checkBreakLine){
                // Máximo da string que cabe na célula
                $strTrimmed = $this->SetStringByMaxWidth($str, $sizeCell, false);
                // qtd de caracteres da string recortada
                $lStr = strlen($strTrimmed);

                if ($lStr < strlen($str)) {
                    $arr[] = $strTrimmed;
                    $arr = $checkBreakLine(substr($str, $lStr, strlen($str)), $sizeCell, $arr);
                } else {
                    $arr[] = $str;
                }

                return $arr;
            };

            $rowsDesc = $checkBreakLine($descricao, 90, []);

            $nRows = count($rowsDesc);
            for($i = 0; $i < $nRows; $i++) {
                if ($i === 0) $addNewRow( $tipoQuebra, $dscEndereco, $codProduto, $rowsDesc[0], $codigoBarras, $referencia, $embalagem, $quantidade, $caixas);
                else {
                    $strDesc = (!empty($rowsDesc[$i])) ? $rowsDesc[$i] : "";
                    $addNewRow( $tipoQuebra, "", "", $strDesc, "", "", "", "", "");
                }
            }


            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }
        //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        /**
         * Cria rodape
         */
        if ($contadorPg > 0) {
            $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total);
        }
    }

    private function layoutModelo6($mapa, $arrDataCargas, $dscBox = null) {

        $this->idMapa = $mapa->getId();

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]" . ' - PLACA: ' . $mapa->getExpedicao()->getPlacaExpedicao(), 0, 1);

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 5, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->Cell(4, 5, utf8_decode("BOX: " . $dscBox), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom

        $this->Cell(4, 10, utf8_decode($mapa->getDscQuebra()), 0, 1);
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, NULL, NULL, 50);

    }


    private function layoutModelo7($mapa, $produtos, $usaGrade, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->AddPage();

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->Cell(20, 4, "", 0, 1);

        $this->SetFont('Arial', 'B', 9);

        if ($usaGrade === 'N') {
            if ($tipoQuebra) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Embalagem"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            } else {
                $this->Cell(60, 5, utf8_decode("Endereço"), 1, 0, 'C');
                $this->Cell(60, 5, utf8_decode("Emb"), 1, 0, 'C');
                $this->Cell(60, 5, utf8_decode("Qtd."), 1, 1, 'C');
                $this->Cell(20, 1, "", 0, 1);
            }
        } else {
            if ($tipoQuebra) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(18, 5, utf8_decode("Emb"), 1, 0); //10
                $this->Cell(18, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
//195
            } else {
                $this->Cell(60, 5, utf8_decode("Endereço"), 1, 0, 'C');
                $this->Cell(60, 5, utf8_decode("Emb"), 1, 0, 'C'); //15
                $this->Cell(60, 5, utf8_decode("Qtd"), 1, 1, 'C');
                $this->Cell(20, 1, "", 0, 1);
            }
        }
        $pesoTotal = 0.0;
        $cubagemTotal = 0;
        /** @var Expedicao\MapaSeparacaoProduto $produto */
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $dscEndereco = "";
            $embalagem = $produto->getProdutoEmbalagem();
            if ($produto->getProdutoVolume() == null) {
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $endereco = $produto->getDepositoEndereco();
            }else{
                $embalagem = $produto->getProdutoVolume()->getDescricao();
                $endereco = $produto->getProdutoVolume()->getEndereco();
            }
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));
            if (!empty($pesoProduto)) {
                $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                $cubagemTotal += ($pesoProduto->getCubagem() * $quantidade);
            }
            $this->SetFont('Arial', null, 9);
            if ($usaGrade === "S") {
                if ($tipoQuebra) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(18, 6, $embalagem, 0, 0);
                    $this->Cell(18, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                } else {
                    $this->Cell(60, 6, $dscEndereco, 0, 0, 'C');
                    $this->Cell(60, 6, $embalagem, 0, 0, 'C');
                    $this->Cell(60, 6, $quantidade, 0, 1, 'C');
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            } else {
                if ($tipoQuebra) {

                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(20, 6, $embalagem, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 2, "", 0, 1);
                } else {
                    $this->Cell(60, 6, $dscEndereco, 0, 0, 'C');
                    $this->Cell(60, 6, $embalagem, 0, 0, 'C');
                    $this->Cell(60, 6, $quantidade, 0, 1, 'C');
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            }
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, 150, 280, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell($wPage * 4, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->Cell($wPage * 4, 6, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 1);
        $this->Cell($wPage * 4, 6, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 1);
        $this->InFooter = false;
    }

    private function layoutModelo8($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());
        /**
         * Cria cabeçalho
         */
        //Select Arial bold 8
        $this->Cell(20, 1, "", 0, 1);
        $total = 0;
        $contadorPg = 0;
        $limitPg = 30;
        $totalPg = ceil(count($produtos) / ($limitPg - 1));
        $pgAtual = 1;
        $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, '1 de ' . $totalPg, false);
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $contadorPg++;
            if ($contadorPg == $limitPg) {
                $contadorPg = 0;
                $pgAtual++;
                /**
                 * Cria rodape
                 */
                $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total);
                /**
                 * Cria cabeçalho
                 */
                $paginas = $pgAtual . ' de ' . $totalPg;
                $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, $paginas);
            }
            $this->SetFont('Arial', null, 9);
            $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

            $codigoBarras = '';
            $embalagem = '';

            $embalagemEn = $produto->getProdutoEmbalagem();
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $codigoBarras = $embalagemEn->getCodigoBarras();
                $embalagem = $embalagemEn->getDescricao() . ' (' . $embalagemEn->getQuantidade() . ')';
            }

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = self::SetStringByMaxWidth(utf8_decode($produto->getProduto()->getDescricao()), 90);
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";

            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();
            if (isset($pesoProduto) && !empty($pesoProduto)) {
                $pesoTotal += Math::multiplicar(str_replace(',', '.', $embalagemEn->getPeso()), str_replace(',', '.', $quantidade));
                $cubagemTotal += Math::multiplicar(str_replace(',', '.', str_replace('.', '', $embalagemEn->getCubagem())), str_replace(',', '.', $quantidade));
            }

            $this->Cell(20, 1, " ", 0, 1);
            if ($tipoQuebra) {
                $this->Cell(23, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(90, 4, $descricao, 0, 0);
                $this->Cell(28, 4, $codigoBarras, 0, 0,'C');
                $this->Cell(13, 4, $embalagem, 0, 0, 'C');
                $this->SetFont('Arial', "B", 10);
                $this->Cell(11, 4, $quantidade, 0, 0,'L');
                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->Cell(23, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(98, 4, $descricao, 0, 0);
                $this->Cell(30, 4, $codigoBarras, 0, 0,'C');
                $this->Cell(17, 4, $embalagem, 0, 0,'C');
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1, 'L');
            }
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->Cell(20, 1, " ", 0, 1);
        }
        //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        /**
         * Cria rodape
         */
        if ($contadorPg > 0) {
            $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total);
        }
    }

    private function layoutModelo9($mapa, $produtos, $usaGrade, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->AddPage();
//Select Arial bold 8

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->Cell(20, 4, "", 0, 1);

        $this->SetFont('Arial', 'B', 9);

        if ($usaGrade === 'N') {
            if ($tipoQuebra) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(19, 5, utf8_decode("Código"), 1, 0);
                $this->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Item"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Lote"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(19, 5, utf8_decode("Código"), 1, 0);
                $this->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Item"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Lote"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Qtd."), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }
        } else {
            if ($tipoQuebra) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(19, 5, utf8_decode("Código"), 1, 0);//3
                $this->Cell(19, 5, utf8_decode("Grade"), 1, 0);//1
                $this->Cell(73, 5, utf8_decode("Produto"), 1, 0);//5
                $this->Cell(15, 5, utf8_decode("Item"), 1, 0);//3
                $this->Cell(15, 5, utf8_decode("Lote"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd."), 1, 0);//3
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
//195
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(19, 5, utf8_decode("Código"), 1, 0);//3
                $this->Cell(19, 5, utf8_decode("Grade"), 1, 0);//1
                $this->Cell(88, 5, utf8_decode("Produto"), 1, 0);//5
                $this->Cell(15, 5, utf8_decode("Item"), 1, 0);//3
                $this->Cell(15, 5, utf8_decode("lote"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd"), 1, 1);//3
                $this->Cell(20, 1, "", 0, 1);
            }
        }
        $pesoTotal = 0;
        $cubagemTotal = 0;
        /** @var Expedicao\MapaSeparacaoProduto $produto */
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $dscEndereco = "";
            $embalagem = $produto->getProdutoEmbalagem();
            $codProduto = $produto->getCodProduto();
            $grade = $produto->getDscGrade();
            $lote = $produto->getLote();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $descricaoView = $descricao;
            if(strlen ( $descricao) > 50) {
                $descricaoView = substr($descricao, 0, 50);
            }
            if ($produto->getProdutoVolume() == null) {
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
            }else{
                $embalagem = $produto->getProdutoVolume()->getDescricao();
            }
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $endereco = $produto->getDepositoEndereco();
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));
            if (!empty($pesoProduto)) {
                $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                $cubagemTotal += ($pesoProduto->getCubagem() * $quantidade);
            }
            $this->SetFont('Arial', null, 9);
            if ($usaGrade === "S") {
                if ($tipoQuebra) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(19, 6, $codProduto, 0, 0);
                    $this->Cell(19, 6, $this->SetStringByMaxWidth($grade, 20), 0, 0);
                    $this->Cell(73, 6, $this->SetStringByMaxWidth($descricao, 80), 0, 0);
                    $this->Cell(15, 6, $embalagem, 0, 0);
                    $this->Cell(15, 6, $lote, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                    if(strlen ( $descricao) > 50) {
                        $this->MultiCell(99, 6, $this->SetStringByMaxWidth(substr($descricao, 50, 300), 99), 3, 0);
                    }
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(19, 6, $codProduto, 0, 0);
                    $this->Cell(19, 6, $this->SetStringByMaxWidth($grade, 20), 0, 0);
                    $this->Cell(88, 6, $this->SetStringByMaxWidth($descricao, 93), 0, 0);
                    $this->Cell(15, 6, $embalagem, 0, 0);
                    $this->Cell(15, 6, $lote, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 1, 'C');
                    if(strlen ( $descricao) > 50) {
                        $this->MultiCell(99, 6, $this->SetStringByMaxWidth(substr($descricao, 50, 300), 99), 3, 0);
                    }
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            } else {
                if ($tipoQuebra) {

                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(19, 6, $codProduto, 0, 0);
                    $this->Cell(90, 6, $this->SetStringByMaxWidth($descricaoView, 90), 0, 0);
                    $this->Cell(15, 6, $embalagem, 0, 0);
                    $this->Cell(15, 6, $lote, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                    if(strlen ( $descricao) > 50) {
                        $this->MultiCell(99, 6, $this->SetStringByMaxWidth(substr($descricao, 50, 300), 99), 3, 0);
                    }
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 2, "", 0, 1);
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(19, 6, $codProduto, 0, 0);
                    $this->Cell(90, 6, $this->SetStringByMaxWidth($descricao, 98), 0, 0);
                    $this->Cell(20, 6, $embalagem, 0, 0);
                    $this->Cell(22, 6, $lote, 0, 0);
                    $this->Cell(20, 6, $quantidade, 0, 1, 'C');
                    if(strlen ( $descricao) > 50) {
                        $this->MultiCell(99, 6, $this->SetStringByMaxWidth(substr($descricao, 50, 300), 99), 3, 0);
                    }
                    $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            }
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, 150, 280, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->Cell(21, 6, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, utf8_decode($this->idExpedicao), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(14, 6, utf8_decode("$arrDataCargas[txt]: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 4, 6, $arrDataCargas['str'], 0, 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 0);
        $this->Cell($wPage * 2, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->InFooter = false;
    }

    /**
     * @param $mapa Expedicao\MapaSeparacao
     * @param $produtos Expedicao\MapaSeparacaoProduto[]
     * @param $usaGrade
     * @param $tipoQuebra
     * @param $dscBox
     * @param $arrDataCargas
     */
    private function layoutModelo10($mapa, $produtos, $usaGrade, $tipoQuebra, $dscBox, $arrDataCargas)
    {
        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->em = \Zend_Registry::get('doctrine')->getEntityManager();
        $pedidoRepository = $this->em->getRepository('wms:Expedicao\Pedido');
        $pessoaEntity = $pedidoRepository->getClienteByMapa($mapa->getId());
        $pessoaEntity = reset($pessoaEntity);

        $this->AddPage();

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("BOX: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $dscBox, 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("DADOS CLIENTE: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $pessoaEntity['nome']. ' - ' .$pessoaEntity['documento'], 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("ENDEREÇO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, trim($pessoaEntity['descricao']).' '.trim($pessoaEntity['numero']).', '.trim($pessoaEntity['bairro']), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("CIDADE/UF: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $pessoaEntity['localidade'].' / '.$pessoaEntity['sigla'], 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("CEP: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $pessoaEntity['cep'], 0, 1);
        $this->Cell(20, 4, "", 0, 1);
        $this->SetFont('Arial', 'B', 9);

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
        $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
        $this->Cell(98, 5, utf8_decode("Produto"), 1, 0);
        $this->Cell(31, 5, utf8_decode("Embalagem"), 1, 0);
        $this->Cell(20, 5, utf8_decode("Qtd."), 1, 1);
        $this->Cell(20, 1, "", 0, 1);

        $pesoTotal = 0.0;
        $cubagemTotal = 0.0;
        $quantidadeTotal = 0;
        /** @var Expedicao\MapaSeparacaoProduto $produto */
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $dscEndereco = "";
            $embalagem = $produto->getProdutoEmbalagem();
            $codProduto = $produto->getCodProduto();
            $grade = $produto->getDscGrade();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $descricaoView = $descricao;
            if(strlen ( $descricao) > 50) {
                $descricaoView = substr($descricao, 0, 50);
            }
            if ($produto->getProdutoVolume() == null) {
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $endereco = $produto->getDepositoEndereco();
            }else{
                $embalagem = $produto->getProdutoVolume()->getDescricao();
                $endereco = $produto->getProdutoVolume()->getEndereco();
            }
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));
            $quantidadeTotal += $quantidade;

            $this->SetFont('Arial', null, 9);
            $this->Cell(24, 6, $dscEndereco, 0, 0);
            $this->Cell(22, 6, $codProduto, 0, 0);
            $this->Cell(98, 6, $this->SetStringByMaxWidth($descricao, 98), 0, 0);
            $this->Cell(31, 6, $embalagem, 0, 0);
            $this->Cell(20, 6, $quantidade, 0, 1, 'C');
            if(strlen ( $descricao) > 50) {
                $this->MultiCell(99, 6, $this->SetStringByMaxWidth(substr($descricao, 50, 300), 99), 3, 0);
            }
            $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->Cell(20, 1, "", 0, 1);
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(190, 10, utf8_decode("TOTAL CAIXAS " . $quantidadeTotal), 0, 1, 'R');
        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, 150, 280, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $mapa->getId()), 0, 1);
        $this->Cell(21, 6, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, utf8_decode($this->idExpedicao), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(14, 6, utf8_decode("$arrDataCargas[txt]: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 4, 6, $arrDataCargas['str'], 0, 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 0);
        $this->Cell($wPage * 2, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->InFooter = false;
    }

    private function layoutModelo11($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());
        /**
         * Cria cabeçalho
         */
        //Select Arial bold 8
        $this->Cell(20, 1, "", 0, 1);
        $total = 0;
        $contadorPg = 0;
        $limitPg = 30;
        $totalPg = ceil(count($produtos) / $limitPg);
        $pgAtual = 1;
        $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, '1 de ' . $totalPg);
        $qtdProdutos = 0;
        $codProdutoAnterior = "";
        foreach ($produtos as $produto) {
            $produto = reset($produto);
            $contadorPg++;
            if ($contadorPg == $limitPg) {
                $contadorPg = 0;
                $pgAtual++;
                /**
                 * Cria rodape
                 */
                $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total, $arrDataCargas, $qtdProdutos);
                $total = 0;
                $qtdProdutos = 0;
                $codProdutoAnterior = "";
                /**
                 * Cria cabeçalho
                 */
                $paginas = $pgAtual . ' de ' . $totalPg;
                $this->buildHead($this, $imgCodBarras, $tipoQuebra, $arrDataCargas, $paginas);
            }
            $this->SetFont('Arial', null, 9);
            $pesoProduto = $this->pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

            $codigoBarras = '';
            $embalagem = '';

            $embalagemEn = $produto->getProdutoEmbalagem();
            $qtdEmbalagem = 1;
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $codigoBarras = '...' . substr($embalagemEn->getCodigoBarras(), -5);
                $embalagem = $embalagemEn->getDescricao() . ' (' . $embalagemEn->getQuantidade() . ')';
                $qtdEmbalagem = $embalagemEn->getQuantidade();
            }

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = self::SetStringByMaxWidth(utf8_decode($produto->getProduto()->getDescricao()), 90);
            $referencia = $produto->getProduto()->getReferencia();
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";

            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if (isset($pesoProduto) && !empty($pesoProduto)) {
                $pesoTotal += ($pesoProduto->getPeso() * $quantidade * $qtdEmbalagem);
                $cubagemTotal += $pesoProduto->getCubagem() * $quantidade * $qtdEmbalagem;
            }

            if ($tipoQuebra) {
                $this->Cell(21, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(90, 4, $descricao, 0, 0);
                $this->Cell(18, 4, $codigoBarras, 0, 0);
                $this->Cell(18, 4, $referencia, 0, 0);
                $this->Cell(19, 4, $embalagem, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 0);
                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->Cell(21, 4, $dscEndereco, 0, 0);
                $this->Cell(13, 4, $codProduto, 0, 0);
                $this->Cell(90, 4, $descricao, 0, 0);
                $this->Cell(18, 4, $codigoBarras, 0, 0);
                $this->Cell(18, 4, $referencia, 0, 0);
                $this->Cell(19, 4, $embalagem, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1, 'C');
            }
            $this->SetFont('Arial', null, 9);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);

            if ($codProduto != $codProdutoAnterior) {
              $qtdProdutos += 1;
            }
            $codProdutoAnterior = $codProduto;
        }
        //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        /**
         * Cria rodape
         */
        if ($contadorPg > 0) {
            $this->buildFooter($this, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total, $arrDataCargas, $qtdProdutos);
        }

    }

    /**
     * @param $mapa Expedicao\MapaSeparacao
     * @param $produtos Expedicao\MapaSeparacaoProduto[]
     * @param $usaGrade
     * @param $tipoQuebra
     * @param $dscBox
     * @param $arrDataCargas
     */
    private function layoutModelo12($mapa, $produtos, $usaGrade, $tipoQuebra, $dscBox, $arrDataCargas)
    {
        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->AddPage();

        $codCargaExterno = explode(',', $arrDataCargas['str']);
        $cargaEn = $this->cargaRepo->findOneBy(array('codCargaExterno' => array($codCargaExterno[0])));
        $pedidosEntities = $this->pedidoRepo->findBy(array('carga' => $cargaEn->getId()), array('linhaEntrega' => 'ASC'));
        $seqRotaPraca = $this->pedidoRepo->getSeqRotaPracaByMapa($this->idMapa);
        $strSeqRota = $seqRotaPraca[0]['SEQ_ROTA'].".".$seqRotaPraca[0]['SEQ_PRACA'];
        $itinerarios = array();
        $ultimaLinha = '';
        foreach ($pedidosEntities as $key => $pedidosEntity) {

            if ($ultimaLinha != $pedidosEntity->getLinhaEntrega()) {
                $itinerarios[] = $pedidosEntity->getLinhaEntrega();
            }

            $ultimaLinha = $pedidosEntity->getLinhaEntrega();

        }
        $linhaSeparacao = implode(', ',$itinerarios);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str] - $linhaSeparacao", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(25, 4, utf8_decode("PLACA/BOX: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(40, 4, $cargaEn->getPlacaCarga().'/'.$dscBox, 0, 0);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(34, 4, utf8_decode("SEQ-ROTA.PRACA:"), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $strSeqRota, 0, 1);

        $pesoTotal = 0.0;
        $cubagemTotal = 0.0;
        /** @var Expedicao\MapaSeparacaoProduto $produto */

//        foreach ($produtos as $produto) {
//            $produto = reset($produto);
//            $quantidade = $produto->getQtdSeparar();
//            if ($produto->getProdutoEmbalagem() != null) {
//                $peso = $produto->getProdutoEmbalagem()->getPeso();
//                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
//            } elseif ($produto->getProdutoVolume() != null) {
//                $peso = $produto->getProdutoVolume()->getPeso();
//                $cubagem = $produto->getProdutoVolume()->getCubagem();
//            }
//            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
//            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));
//        }

        $this->Cell(20, 4, "", 0, 1);

        $this->SetFont('Arial', 'B', 9);

        if ($usaGrade === 'N') {
            if ($tipoQuebra == true) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(99, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Embalagem"), 1, 0);
                $this->Cell(15, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(98, 5, utf8_decode("Produto"), 1, 0);
                $this->Cell(31, 5, utf8_decode("Emb"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Qtd."), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }
        } else {
            if ($tipoQuebra == true) {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                $this->Cell(78, 5, utf8_decode("Produto"), 1, 0); //20
                $this->Cell(18, 5, utf8_decode("Emb"), 1, 0); //10
                $this->Cell(18, 5, utf8_decode("Qtd."), 1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
//195
            } else {
                $this->Cell(24, 5, utf8_decode("Endereço"), 1, 0);
                $this->Cell(22, 5, utf8_decode("Cod.Produto"), 1, 0);
                $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                $this->Cell(93, 5, utf8_decode("Produto"), 1, 0); //10
                $this->Cell(18, 5, utf8_decode("Emb"), 1, 0); //15
                $this->Cell(18, 5, utf8_decode("Qtd"), 1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }
        }

        $addNewRow = function ($usaGrade, $tipoQuebra, $dscEndereco, $codProduto, $grade, $descricao, $embalagem, $quantidade, $caixas) {
            if ($usaGrade === "S") {
                if ($tipoQuebra == true) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(20, 6, $grade, 0, 0);
                    $this->Cell(78, 6, $descricao, 0, 0);
                    $this->Cell(18, 6, $embalagem, 0, 0);
                    $this->Cell(18, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(20, 6, $grade, 0, 0);
                    $this->Cell(93, 6, $descricao, 0, 0);
                    $this->Cell(18, 6, $embalagem, 0, 0);
                    $this->Cell(18, 6, $quantidade, 0, 1, 'C');
                }
            } else {
                if ($tipoQuebra == true) {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(99, 6, $descricao, 0, 0);
                    $this->Cell(20, 6, $embalagem, 0, 0);
                    $this->Cell(15, 6, $quantidade, 0, 0);
                    $this->Cell(15, 6, $caixas, 0, 1, 'C');
                } else {
                    $this->Cell(24, 6, $dscEndereco, 0, 0);
                    $this->Cell(22, 6, $codProduto, 0, 0);
                    $this->Cell(98, 6, $descricao, 0, 0);
                    $this->Cell(31, 6, $embalagem, 0, 0);
                    $this->Cell(20, 6, $quantidade, 0, 1, 'C');
                }
            }
        };

        $checkBreakLine = function ($str, $sizeCell, $arr) use (&$checkBreakLine){
            // Máximo da string que cabe na célula
            $strTrimmed = $this->SetStringByMaxWidth($str, $sizeCell, false);
            // qtd de caracteres da string recortada
            $lStr = strlen($strTrimmed);

            if ($lStr < strlen($str)) {
                $arr[] = $strTrimmed;
                $arr = $checkBreakLine(substr($str, $lStr, strlen($str)), $sizeCell, $arr);
            } else {
                $arr[] = $str;
            }

            return $arr;
        };

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

        foreach ($produtos as $produto) {
            $produto = reset($produto);

            $dscEndereco = "";
            $embalagem = $produto->getProdutoEmbalagem();
            $codProduto = $produto->getCodProduto();
            $grade = $produto->getDscGrade();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());

            if ($produto->getProdutoVolume() == null) {
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $endereco = $produto->getDepositoEndereco();
            }else{
                $embalagem = $produto->getProdutoVolume()->getDescricao();
                $endereco = $produto->getProdutoVolume()->getEndereco();
            }
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            $this->SetFont('Arial', null, 9);

            $rowsGrade[0] = $grade;
            if ($usaGrade)
                $rowsGrade = $checkBreakLine($grade, 20, []);

            if ($usaGrade === "S") {
                if ($tipoQuebra == true) {
                    $wDesc = 78;
                } else {
                    $wDesc = 93;
                }
            } else {
                if ($tipoQuebra == true) {
                    $wDesc = 99;
                } else {
                    $wDesc = 98;
                }
            }

            $rowsDesc = $checkBreakLine($descricao, $wDesc, []);

            $nRows = (count($rowsGrade) < count($rowsDesc)) ? count($rowsDesc) : count($rowsGrade);
            for($i = 0; $i < $nRows; $i++) {
                if ($i === 0) $addNewRow($usaGrade, $tipoQuebra, $dscEndereco, $codProduto, $rowsGrade[0], $rowsDesc[0], $embalagem, $quantidade, $caixas);
                else {
                    $strGrade = (!empty($rowsGrade[$i])) ? $rowsGrade[$i] : "";
                    $strDesc = (!empty($rowsDesc[$i])) ? $rowsDesc[$i] : "";
                    $addNewRow($usaGrade, $tipoQuebra, "", "", $strGrade, $strDesc, "", "", "");
                }
            }

            $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->Cell(20, 1, "", 0, 1);
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $this->Image($imgCodBarras, 150, 280, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $mapa->getId()), 0, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(15, 6, "PLACA: ", 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, $cargaEn->getPlacaCarga(). ' - ' . substr($linhaSeparacao,0,30), 0, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(23, 6, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, utf8_decode($this->idExpedicao), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 6, utf8_decode("$arrDataCargas[txt]: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 4, 6, $arrDataCargas['str'], 0, 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . number_format($this->cubagemCarga,2,',','')), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . number_format($this->pesoCarga,2,',','')), 0, 0);
        $this->Cell($wPage * 2, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->InFooter = false;
    }

    private function layoutModelo13($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

//Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, '', 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

        $this->Image($imgCodBarras, 150, 3, 50);
        $this->Cell(20, 4, "", 0, 1);
        $this->SetFont('Arial', 'B', 8);

        if ($tipoQuebra) {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(15, 5, utf8_decode("Cod.Prod."), 1, 0);
            $this->Cell(60, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Qtd."), 1, 0);
            $this->Cell(15, 5, utf8_decode("Desc"), 1, 0);
            $this->Cell(17, 5, utf8_decode("Caixas"), 1, 0);
            $this->Cell(17, 5, utf8_decode("Obs."), 1, 1);
        } else {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Quant."), 1, 0);
            $this->Cell(40, 5, utf8_decode("Desc"), 1, 1);
        }

        $this->Cell(20, 1, "", 0, 1);

        $total = 0;
        $y1 = 35;
        $y2 = 50;
        foreach ($produtos as $key => $produto) {
            $objectIndex = $produto;
            $produto = reset($produto);
            $this->SetFont('Arial', null, 8);
            $embalagemEn = $this->embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
            $volumeEn = $produto->getProdutoVolume();

            $embalagem = $produto->getProdutoEmbalagem();

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";
            $codigoBarras = '';
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $codigoBarras = $embalagemEn->getCodigoBarras();
                $embalagem = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
            } elseif (isset($volumeEn) && !empty($volumeEn)) {
                $codigoBarras = $volumeEn->getDescricao();
                $embalagem = $produto->getProdutoVolume()->getDescricao();
            }

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            $h = 4;

            if ($tipoQuebra) {
                $this->Cell(20, $h, $dscEndereco, 0, 0);
                $this->Cell(15, $h, $codProduto, 0, 0);
                $this->MultiCell(60, $h, $descricao,3);
                $this->setXY(105,$this->getY());
                $this->Cell(30, -4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, -4, $quantidade, 0, 0);
                $this->Cell(13, -4, $embalagem, 0, 0);
                $this->Cell(12, -4, $caixas, 0, 0, 'C');

                $this->Cell(5, 6, '', 0, 1, 'L');
            } else {
                $this->Cell(20, $h, $dscEndereco, 0, 0);
                $this->Cell(20, $h, $codProduto, 0, 0);
                $this->MultiCell(60, $h, $descricao, 3);
                $this->Cell(20, $h, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, $h, $quantidade, 0, 0, 'C');
                $this->Cell(40, $h, $embalagem, 0, 1, 'C');
            }
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $y2 = $y2 + 10;


            if ($tipoQuebra) {
                $this->Line(179,$y1,179,$this->getY());
                $this->Line(196,$y1,196,$this->getY());
            }
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }

//FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode("- - - - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell($wPage * 5, 6, utf8_decode("EXPEDIÇÃO ") . $this->idExpedicao . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
        $this->Cell($wPage * 2.5, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 0);
        $this->Cell($wPage * 2.5, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);

        $this->Image($this->imgCodBarras, 143, 280, 50);
        $this->InFooter = false;
    }

    private function layoutModelo14($mapa, $produtos, $tipoQuebra, $arrDataCargas) {

        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();
        $pesoTotal = 0.0;
        $cubagemTotal = 0;

        $this->AddPage();

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);

        $pessoaEntity = $this->pedidoRepo->getClienteByMapa($mapa->getId());
        $pessoaEntity = reset($pessoaEntity);

//Select Arial bold 8
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 0);

        $this->currentPage += 1;
        $this->SetFont('Arial', 'B', 11.5);
        $this->SetX(180);
        $this->Cell(10, 5, $this->currentPage . "/" . $this->countPages, 0, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(35,4, utf8_decode('HORA IMPRESSÃO: '), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(32, 4, utf8_decode("DADOS CLIENTE: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $pessoaEntity['nome']. ' - ' .$pessoaEntity['documento'], 0, 1);

        $this->Image($imgCodBarras, 150, 3, 50);
        $this->Cell(20, 4, "", 0, 1);
        $this->SetFont('Arial', 'B', 8);

        if ($tipoQuebra) {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(17, 5, utf8_decode("Cod.Prod."), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(15, 5, utf8_decode("Embal."), 1, 0);
            $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Qtd."), 1, 1);
//            $this->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
        } else {
            $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
            $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
            $this->Cell(40, 5, utf8_decode("Embal."), 1, 0);
            $this->Cell(20, 5, utf8_decode("Cod. Barras"), 1, 0);
            $this->Cell(12, 5, utf8_decode("Quant."), 1, 1);
        }

        $this->Cell(20, 1, "", 0, 1);

        $total = 0;
        $contadorPg = 0;
        $limitPg = 42;
        $pgAtual = 1;

        foreach ($produtos as $produto) {
            $produto = reset($produto);


            $contadorPg++;
            if ($contadorPg == $limitPg) {
                $contadorPg = 0;
                $pgAtual++;


                $this->AddPage();
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
                $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
                $this->Cell(20, 3, "", 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 0);

                $this->currentPage += 1;
                $this->SetFont('Arial', 'B', 11.5);
                $this->SetX(180);
                $this->Cell(10, 5, $this->currentPage . "/" . $this->countPages, 0, 1);

                $this->SetFont('Arial', 'B', 10);
                $this->Cell(35,4, utf8_decode('HORA IMPRESSÃO: '), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(4, 4, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(32, 4, utf8_decode("DADOS CLIENTE: "), 0, 0);
                $this->SetFont('Arial', null, 10);
                $this->Cell(20, 4, $pessoaEntity['nome']. ' - ' .$pessoaEntity['documento'], 0, 1);

                $this->Cell(20, 4, "", 0, 1);
                $this->SetFont('Arial', 'B', 8);

                if ($tipoQuebra) {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(17, 5, utf8_decode("Cod.Prod."), 1, 0);
                    $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(15, 5, utf8_decode("Embal."), 1, 0);
                    $this->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
                    $this->Cell(12, 5, utf8_decode("Qtd."), 1, 1);
//            $this->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
                } else {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(85, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(40, 5, utf8_decode("Embal."), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod. Barras"), 1, 0);
                    $this->Cell(12, 5, utf8_decode("Quant."), 1, 1);
                }

                $this->Cell(20, 1, "", 0, 1);
            }

            $this->SetFont('Arial', null, 8);
            $embalagemEn = $this->embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
            $volumeEn = $produto->getProdutoVolume();

            $endereco = $produto->getDepositoEndereco();
            $codProduto = $produto->getCodProduto();
            $descricao = utf8_decode($produto->getProduto()->getDescricao());
            $quantidade = $produto->getQtdSeparar();
            $caixas = $produto->getNumCaixaInicio() . ' - ' . $produto->getNumCaixaFim();
            $dscEndereco = "";
            $codigoBarras = '';
            if ($endereco != null)
                $dscEndereco = $endereco->getDescricao();

            if ($produto->getProdutoEmbalagem() != null) {
                $peso = $produto->getProdutoEmbalagem()->getPeso();
                $cubagem = $produto->getProdutoEmbalagem()->getCubagem();
                $dscEmbalagem = $produto->getProdutoEmbalagem()->getDescricao().' ('.$produto->getProdutoEmbalagem()->getQuantidade().')';
                $codigoBarras = $produto->getProdutoEmbalagem()->getCodigoBarras();
            }
            if ($produto->getProdutoVolume() != null) {
                $peso = $produto->getProdutoVolume()->getPeso();
                $cubagem = $produto->getProdutoVolume()->getCubagem();
                $dscEmbalagem = $produto->getProdutoVolume()->getDescricao();
            }
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            if ($tipoQuebra) {
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(17, 4, $codProduto, 0, 0);
                $this->Cell(85, 4, substr($descricao, 0, 45), 0, 0);
                $this->Cell(15, 4, $dscEmbalagem, 0, 0);
                $this->Cell(30, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1);
//                $this->Cell(15, 4, $caixas, 0, 1, 'C');
            } else {
                $this->Cell(20, 4, $dscEndereco, 0, 0);
                $this->Cell(20, 4, $codProduto, 0, 0);
                $this->Cell(85, 4, substr($descricao, 0, 57), 0, 0);
                $this->Cell(40, 4, $dscEmbalagem, 0, 0);
                $this->Cell(20, 4, $codigoBarras, 0, 0);
                $this->SetFont('Arial', "B", 10);
                $this->Cell(15, 4, $quantidade, 0, 1, 'C');
            }
            $this->SetFont('Arial', null, 8);
            $total += $quantidade;
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
        }

//FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
        $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $this->Cell($wPage * 4, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->Cell($wPage * 4, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
        $this->Cell($wPage * 4, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

        $this->Image($this->imgCodBarras, 143, 280, 50);
        $this->InFooter = false;
    }

    /**
     * @param $mapa Expedicao\MapaSeparacao
     * @param $produtos array
     * @param $usaGrade
     * @param $dscBox
     * @param $arrDataCargas
     */
    private function layoutMapaFracionaveis($mapa, $produtos, $usaGrade, $dscBox, $arrDataCargas)
    {
        $this->idMapa = $mapa->getId();
        $this->quebrasEtiqueta = $mapa->getDscQuebra();

        $this->AddPage('L');

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(270, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1, "C");
        $this->Cell(20, 1, "____________________________________________________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(4, 4, utf8_decode($this->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 4, utf8_decode("BOX: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(20, 4, $dscBox, 0, 1);
        $this->Cell(20, 4, "", 0, 1);

        $this->SetFont('Arial', 'B', 9);

        $arrWidthCols[0] = 24;
        $arrWidthCols[1] = 22;
        if ($usaGrade === 'N') {
            $arrWidthCols[2] = 98;
        } else {
            $arrWidthCols[2] = 20;
            $arrWidthCols[3] = 93;
        }
        $arrWidthCols[4] = 20;
        $arrWidthCols[5] = 15;
        $arrWidthCols[6] = 98;

        $this->Cell($arrWidthCols[0], 5, utf8_decode("Endereço"), 1, 0);
        $this->Cell($arrWidthCols[1], 5, utf8_decode("Cod.Produto"), 1, 0);

        if ($usaGrade === 'N') {
            $this->Cell($arrWidthCols[2], 5, utf8_decode("Produto"), 1, 0);
        } else {
            $this->Cell($arrWidthCols[2], 5, utf8_decode("Grade"), 1, 0);
            $this->Cell($arrWidthCols[3], 5, utf8_decode("Produto"), 1, 0); //10
        }

        $this->Cell($arrWidthCols[4], 5, utf8_decode("Emb"), 1, 0); //15
        $this->Cell($arrWidthCols[5], 5, utf8_decode("Qtd"), 1, 0);
        $this->Cell($arrWidthCols[6], 5, utf8_decode("Cliente"), 1, 1);

        $this->Cell(20, 1, "", 0, 1);

        $pesoTotal = 0;
        $cubagemTotal = 0;


        foreach ($produtos as $arg) {
            /** @var Expedicao\MapaSeparacaoProduto $mapaProduto */
            $mapaProduto = $arg[0];
            $codProduto = $mapaProduto->getCodProduto();
            $grade = $mapaProduto->getDscGrade();
            $descricao = utf8_decode($mapaProduto->getProduto()->getDescricao());
            $cliente = $mapaProduto->getPedidoProduto()->getPedido()->getPessoa();
            $nomCliente = $cliente->getPessoa()->getNome();
            $codCliente = $cliente->getCodClienteExterno();

            /** @var Produto\Embalagem|Produto\Volume $elemento */
            $elemento = null;
            $dscElemento = '';
            if ($mapaProduto->getProduto()->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO) {
                $elemento = $mapaProduto->getProdutoEmbalagem();
                $dscElemento = $elemento->getDescricao() . ' (' . $elemento->getQuantidade() . ')';
            } elseif ($mapaProduto->getProduto()->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO) {
                $elemento = $mapaProduto->getProdutoVolume();
                $dscElemento = $elemento->getDescricao();
            }

            $quantidade = $mapaProduto->getQtdSeparar();

            $dscEndereco = "";
            $endereco = $mapaProduto->getDepositoEndereco();
            if (!empty($endereco)){
                $dscEndereco = $endereco->getDescricao();
            }

            $peso = $elemento->getPeso();
            $cubagem = $elemento->getCubagem();
            $pesoTotal += ($quantidade * str_replace(",",".",$peso));
            $cubagemTotal += ($quantidade * str_replace(",",".",$cubagem));

            $this->SetFont('Arial', null, 9);

            $this->Cell($arrWidthCols[0], 6, $dscEndereco, 0, 0);
            $this->Cell($arrWidthCols[1], 6, $codProduto, 0, 0);

            if ($usaGrade === "N") {
                $this->Cell($arrWidthCols[2], 6, $this->SetStringByMaxWidth($descricao, 98), 0, 0);
            } else {
                $this->Cell($arrWidthCols[2], 6, $this->SetStringByMaxWidth($grade, 20), 0, 0);
                $this->Cell($arrWidthCols[3], 6, $this->SetStringByMaxWidth($descricao, 93), 0, 0);
            }

            $this->Cell($arrWidthCols[4], 6, $dscElemento, 0, 0);
            $this->Cell($arrWidthCols[5], 6, $quantidade, 0, 0);
            $this->Cell($arrWidthCols[6], 6, $this->SetStringByMaxWidth("$codCliente  -  $nomCliente", $arrWidthCols[6]-2), 0, 1);

            $this->Cell(20, 2, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->Cell(20, 1, "", 0, 1);
        }

        $this->SetFont('Arial', 'B', 9);

        $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
        $this->SetFont('Arial', 'B', 7);
//Go to 1.5 cm from bottom
        $this->Cell(20, 3, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1, "L");

        $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
        $this->Image($imgCodBarras, 220, 195, 50);

        $this->InFooter = true;
        $pageSizeA4 = $this->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $this->SetY(-23);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell(191, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $mapa->getId()), 0, 1);
        $this->Cell(21, 6, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 1, 6, utf8_decode($this->idExpedicao), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(14, 6, utf8_decode("$arrDataCargas[txt]: "), 0, 0);
        $this->SetFont('Arial', null, 10);
        $this->Cell($wPage * 4, 6, $arrDataCargas['str'], 0, 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 0);
        $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 0);
        $this->Cell($wPage * 2, 6, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $this->InFooter = false;
    }

    /**
     * @return mixed
     */
    public function getCargasSelecionadas() {
        return $this->cargasSelecionadas;
    }

    /**
     * @param mixed $cargasSelecionadas
     */
    public function setCargasSelecionadas($cargasSelecionadas) {
        $this->cargasSelecionadas = $cargasSelecionadas;
    }

    public function Header() {
        
    }

    public function Footer() {
        
    }

    public function buildHead($object, $imgCodBarras, $tipoQuebra, $arrDataCargas, $paginas = '', $ref = true) {
        $stringCargas = $arrDataCargas['str'];
        $vetCargas = explode(',',$stringCargas);
        $object->SetFont('Arial', 'B', 10);
        $object->Cell(200, 3, utf8_decode($paginas), 0, 1);
        $object->Cell(200, 3, utf8_decode(" MAPA DE SEPARAÇÃO " . $object->idMapa), 0, 1, "C");
        $object->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
        $object->Cell(20, 3, "", 0, 1);
        $object->SetFont('Arial', 'B', 10);
        $object->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
        if(count($vetCargas) <= 11) {
            $object->SetFont('Arial', null, 10);
            $object->Cell(4, 4, utf8_decode($object->idExpedicao) . " - $arrDataCargas[txt]: $arrDataCargas[str]", 0, 1);
        }else{
            $count = 0;
            $stringCargas = '';
            $inicio = utf8_decode($object->idExpedicao) . ' - CARGAS: ';
            foreach ($vetCargas as $cargas){
                $count++;
                if($count == 0){
                    $stringCargas .= $cargas;
                }else{
                    $stringCargas .= ', '.$cargas;
                }
                if($count == 11){
                    $object->SetFont('Arial', null, 10);
                    $object->Cell(4, 4, $inicio . $stringCargas, 0, 1);
                    $count = 0;
                    $stringCargas = '';
                    $inicio = '';
                }
            }
            if($count < 5){
                $object->SetFont('Arial', null, 10);
                $object->Cell(4, 4, $stringCargas, 0, 1);
            }
        }
        $object->SetFont('Arial', null, 10);
        $object->Cell(4, 4, '', 0, 1);
        $object->SetFont('Arial', 'B', 10);
        $object->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
        $object->SetFont('Arial', null, 10);
        $object->Cell(20, 4, utf8_decode($object->quebrasEtiqueta), 0, 1);

        $object->Image($imgCodBarras, 150, 3, 50);
        $object->Cell(20, 4, "", 0, 1);
        $object->SetFont('Arial', 'B', 8);

        if($ref == true) {
            if ($tipoQuebra) {
                $object->Cell(21, 5, utf8_decode("Endereço"), 1, 0);
                $object->Cell(13, 5, utf8_decode("Cod."), 1, 0);
                $object->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                $object->Cell(18, 5, utf8_decode("Cod. Barras"), 1, 0);
                $object->Cell(18, 5, utf8_decode("Refer."), 1, 0);
                $object->Cell(19, 5, utf8_decode("Emb."), 1, 0);
                $object->Cell(15, 5, utf8_decode("Qtd."), 1, 0);
                $object->Cell(17, 5, utf8_decode("Caixas"), 1, 1);
            } else {
                $object->Cell(21, 5, utf8_decode("Endereço"), 1, 0);
                $object->Cell(13, 5, utf8_decode("Cod."), 1, 0);
                $object->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                $object->Cell(18, 5, utf8_decode("Cod. Barras"), 1, 0);
                $object->Cell(18, 5, utf8_decode("Refer."), 1, 0);
                $object->Cell(19, 5, utf8_decode("Emb."), 1, 0);
                $object->Cell(15, 5, utf8_decode("Qtd."), 1, 1);
            }
        }else{
            if ($tipoQuebra) {
                $object->Cell(23, 5, utf8_decode("Endereço"), 1, 0);
                $object->Cell(13, 5, utf8_decode("Cod."), 1, 0);
                $object->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                $object->Cell(28, 5, utf8_decode("Cod. Barras"), 1, 0);
                $object->Cell(13, 5, utf8_decode("Emb."), 1, 0);
                $object->Cell(11, 5, utf8_decode("Qtd."), 1, 0);
                $object->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
            } else {
                $object->Cell(23, 5, utf8_decode("Endereço"), 1, 0);
                $object->Cell(13, 5, utf8_decode("Cod."), 1, 0);
                $object->Cell(98, 5, utf8_decode("Produto"), 1, 0);
                $object->Cell(30, 5, utf8_decode("Cod. Barras"), 1, 0);
                $object->Cell(17, 5, utf8_decode("Emb."), 1, 0);
                $object->Cell(12, 5, utf8_decode("Qtd."), 1, 1);
            }
        }
        return $object;
    }

    public function buildFooter($object, $imgCodBarras, $cubagemTotal, $pesoTotal, $mapa, $total, $carga = null, $qtdProdutos = null) {
        $this->currentPage += 1;

        $object->SetFont('Arial', null, 10);
        $object->Cell(20, 4, utf8_decode(" "), 0, 1);
        $object->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

        $this->total = $total;
        $this->imgCodBarras = $imgCodBarras;
        $this->cubagemTotal = $cubagemTotal;
        $this->pesoTotal = $pesoTotal;
        $this->mapa = $mapa;

        $object->InFooter = true;
        $pageSizeA4 = $object->_getpagesize();
        $wPage = $pageSizeA4[0] / 12;

        $object->SetY(-38);

        $object->SetFont('Arial', 'B', 10);
        $object->Cell(20, 6, "_______________________________________________________________________________________________", 0, 1);

        $txtCarga = "";
        $txtCodCarga = "";
        if ($carga != null) {
            $txtCodCarga = $carga['str'];
            $txtCarga = $carga['txt'] . ': ';
        }

        $object->SetFont('Arial', 'B', 10);
        $object->Cell(15,5,$txtCarga,0,0);
        $object->SetFont('Arial', null, 10);
        $object->Cell(119,5,$txtCodCarga,0,0);

        $object->SetFont('Arial', null, 9);
        $object->Cell($wPage * 11, 5, utf8_decode("PESO MAPA: " . number_format($this->pesoTotal,2,",",".")), 0, 1);

        $object->SetFont('Arial', 'B', 10);
        $object->Cell(23, 5, utf8_decode("ITINERARIO: "), 0, 0);
        $object->SetFont('Arial', null, 10);
        $object->Cell(111, 5, self::SetStringByMaxWidth(utf8_decode($this->itinerarios), 120), 0, 0);

        $object->SetFont('Arial', null, 9);
        $object->Cell($wPage * 11, 5, utf8_decode("QTD. VOLUMES: $this->total"), 0, 1);

        $object->SetFont('Arial', 'B', 10);
        $object->Cell(20, 5, utf8_decode("QUEBRAS: "), 0, 0);
        $object->SetFont('Arial', null, 10);
        $object->Cell(114, 5, utf8_decode($this->quebrasEtiqueta), 0, 0);

        $object->SetFont('Arial', null, 9);
        $txtProduto = "";
        if ($qtdProdutos != null) $txtProduto = utf8_decode("QTD. PRODUTOS: $qtdProdutos");
        $object->Cell($wPage * 11, 5, $txtProduto , 0, 1);

        $object->SetFont('Arial', 'B', 9);
        $object->Cell($wPage * 3.2, 5, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
        $object->Cell($wPage * 2.2, 5, utf8_decode("EXPEDIÇÃO: " . $this->idExpedicao), 0, 0);
        $object->Cell($wPage * 3, 5, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 1);
        $object->Cell($wPage * 3.2, 5, utf8_decode("CUBAGEM TOTAL " .  number_format($this->cubagemTotal, 2, ',', '.')), 0, 0);
        $object->Cell($wPage * 2.9, 5, utf8_decode("PESO TOTAL " . number_format($this->pesoCarga, 2, ',', '.')), 0, 0);
        $object->Cell($wPage * 3, 5, $this->currentPage . "/" . $this->countPages, 0, 1);

        $object->Image($imgCodBarras, 143, 280, 50);

        $object->InFooter = false;

        return $object;
    }

}
