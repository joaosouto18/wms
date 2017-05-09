<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Util\Barcode\Barcode;

use Wms\Util\Barcode\eFPDF;

class MapaSeparacao extends eFPDF
{
    private $idMapa;
    private $idExpedicao;
    private $quebrasEtiqueta;
    private $pesoTotal, $cubagemTotal, $mapa, $imgCodBarras, $total;
    protected $chaveCargas;
    protected $cargasSelecionadas;

    //($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
    public function layoutMapa($expedicao, $modelo, $codBarras = null, $status)
    {
        switch ($modelo) {
            case 2:
                $this->layoutModelo2($expedicao,$status,$codBarras);
                break;
            case 3:
                $this->layoutModelo3($expedicao,$status,$codBarras);
                break;
            case 4:
                $this->layoutModelo4($expedicao,$status,$codBarras);
                break;
            default:
                $this->layoutModelo1($expedicao,$status,$codBarras);
        }
    }

    private function layoutModelo1($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        if ($codBarras == null) {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('expedicao' => $idExpedicao, 'codStatus' => $status));
        } else {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->getMapaSeparacaoById($codBarras);
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var Parametro $param */
        $param = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => "UTILIZA_GRADE"));
        if (!empty($param)){
            $usaGrade = $param->getValor();
        } else {
            $usaGrade = 'N';
        }

        foreach ($mapaSeparacao as $mapa) {
            $produtos        = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto')->getMapaProduto($mapa->getId());
            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
            $quebras         = $mapa->getDscQuebra();
            $tipoQebra       = false;
            if (isset($mapaQuebra) && !empty($mapaQuebra))
                $tipoQebra   = true;

            $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
            $em->persist($mapa);

            $this->idMapa = $mapa->getId();
            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao = $idExpedicao;

            $this->AddPage();

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $em->getRepository('wms:Expedicao');
            $txtCarga = 'CARGA';
            $cargasSelecionadas = $this->getCargasSelecionadas();
            if (empty($cargasSelecionadas)) {
                $cargas = $expedicaoRepo->getCodCargasExterno($this->idExpedicao);
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
                    if (count($cargasSelecionadas) > 1) $txtCarga = 'CARGAS';
                } else {
                    $stringCargas = $cargasSelecionadas;
                }
            }

            //Select Arial bold 8

            $this->SetFont('Arial','B',10);
            $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1,"C");
            $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
            $this->Cell(20, 3, "", 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, utf8_decode( $this->idExpedicao) . ' - '.$txtCarga.': ' . $stringCargas, 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
            $this->Cell(20, 4, "", 0, 1);

            $this->SetFont('Arial', 'B', 8);

            if ($usaGrade === 'N') {
                if ($tipoQebra == true) {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(90, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(30, 5, utf8_decode("Embalagem"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Quantidade"), 1, 0);
                    $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                    $this->Cell(20, 1, "", 0, 1);
                } else {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(100, 5, utf8_decode("Produto"), 1, 0);
                    $this->Cell(35, 5, utf8_decode("Embalagem"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Quantidade"), 1, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            } else {
                if ($tipoQebra == true) {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                    $this->Cell(80, 5, utf8_decode("Produto"), 1, 0);//20
                    $this->Cell(20, 5, utf8_decode("Embalagem"), 1, 0);//10
                    $this->Cell(20, 5, utf8_decode("Quantidade"), 1, 0);
                    $this->Cell(15, 5, utf8_decode("Caixas"), 1, 1);
                    $this->Cell(20, 1, "", 0, 1);
                    //195
                } else {
                    $this->Cell(20, 5, utf8_decode("Endereço"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Cod.Produto"), 1, 0);
                    $this->Cell(20, 5, utf8_decode("Grade"), 1, 0);
                    $this->Cell(95, 5, utf8_decode("Produto"), 1, 0);//10
                    $this->Cell(20, 5, utf8_decode("Embalagem"), 1, 0);//15
                    $this->Cell(20, 5, utf8_decode("Quantidade"), 1, 1);
                    $this->Cell(20, 1, "", 0, 1);
                }
            }

            /** @var Expedicao\MapaSeparacaoProduto $produto */
            foreach ($produtos as $produto) {
                $dscEndereco = "";
                $embalagem   = $produto->getProdutoEmbalagem();
                $codProduto  = $produto->getCodProduto();
                $grade       = $produto->getDscGrade();
                $descricao   = utf8_decode($produto->getProduto()->getDescricao());
                $embalagem   = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $quantidade  = $produto->getQtdSeparar();
                $caixas      = $produto->getNumCaixaInicio().' - '.$produto->getNumCaixaFim();
                $endereco    = $produto->getCodDepositoEndereco();
                if ($endereco != null)
                    $dscEndereco = $endereco->getDescricao();

                $this->SetFont('Arial',  null, 8);
                if ($usaGrade === "S") {
                    if ($tipoQebra == true) {
                        $this->Cell(20, 4, $dscEndereco, 0, 0);
                        $this->Cell(20, 4, $codProduto, 0, 0);
                        $this->Cell(20, 4, $this->SetStringByMaxWidth($grade, 20), 0, 0);
                        $this->Cell(80, 4, $this->SetStringByMaxWidth($descricao, 80), 0, 0);
                        $this->Cell(20, 4, $embalagem, 0, 0);
                        $this->Cell(20, 4, $quantidade, 0, 0);
                        $this->Cell(15, 4, $caixas, 0, 1, 'C');
                        $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                        $this->Cell(20, 1, "", 0, 1);
                    } else {
                        $this->Cell(20, 4, $dscEndereco, 0, 0);
                        $this->Cell(20, 4, $codProduto, 0, 0);
                        $this->Cell(20, 4, $this->SetStringByMaxWidth($grade, 25), 0, 0);
                        $this->Cell(95, 4, $this->SetStringByMaxWidth($descricao, 95), 0, 0);
                        $this->Cell(20, 4, $embalagem, 0, 0);
                        $this->Cell(20, 4, $quantidade, 0, 1, 'C');
                        $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                        $this->Cell(20, 1, "", 0, 1);
                    }
                } else {
                    if ($tipoQebra == true) {
                        $this->Cell(20, 4, $dscEndereco ,0, 0);
                        $this->Cell(20, 4, $codProduto ,0, 0);
                        $this->Cell(90, 4,substr($descricao,0,54) ,0, 0);
                        $this->Cell(30, 4, $embalagem ,0, 0);
                        $this->Cell(20, 4, $quantidade ,0, 0);
                        $this->Cell(15, 4, $caixas ,0, 1, 'C');
                        $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                        $this->Cell(20, 1, "", 0, 1);
                    } else {
                        $this->Cell(20, 4, $dscEndereco ,0, 0);
                        $this->Cell(20, 4, $codProduto ,0, 0);
                        $this->Cell(100, 4,substr($descricao,0,54) ,0, 0);
                        $this->Cell(35, 4, $embalagem ,0, 0);
                        $this->Cell(20, 4, $quantidade ,0, 1, 'C');
                        $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                        $this->Cell(20, 1, "", 0, 1);
                    }
                }
            }

            $this->SetFont('Arial',null,10);
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->SetFont('Arial','B',9);

            $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
            $this->SetFont('Arial','B',7);
            //Go to 1.5 cm from bottom
            $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");

            //$this->SetY(-92);
            $imgCodBarras = @CodigoBarras::gerarNovo($this->idMapa);
            $this->Image($imgCodBarras, 150, 280, 50);

            $this->InFooter = true;
            $pageSizeA4 = $this->_getpagesize();
            $wPage = $pageSizeA4[0]/12;

            $this->SetY(-23);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
            //$this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

            $this->SetFont('Arial','B',9);
            $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
            $this->Cell($wPage * 4, 6, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1);
            //$this->Cell($wPage * 4, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
            //$this->Cell($wPage * 4, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

            //$this->Image($this->imgCodBarras, 143, 280, 50);
            $this->InFooter = false;

        }

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo      = $em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $ExpedicaoRepo->find($idExpedicao);
        $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
        $ExpedicaoEntity->setStatus($statusEntity);
        $em->persist($ExpedicaoEntity);

        $this->Output('Mapa Separação-'.$idExpedicao.'.pdf','D');

        $em->flush();
        $em->clear();
    }

    private function layoutModelo2($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        if ($codBarras == null) {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('expedicao' => $idExpedicao, 'codStatus' => $status));
        } else {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->getMapaSeparacaoById($codBarras);
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');

        $pesoProdutoRepo = $em->getRepository('wms:Produto\Peso');
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $expedicaoRepo            = $em->getRepository('wms:Expedicao');

        foreach ($mapaSeparacao as $mapa) {

            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
            $tipoQebra = false;
            if (isset($mapaQuebra) && !empty($mapaQuebra))
                $tipoQebra = true;

            $produtos        = $mapaSeparacaoProdutoRepo->getMapaProduto($mapa->getId());


            $quebras = $mapa->getDscQuebra();

            if ($mapa->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
                $em->persist($mapa);
            }

            $this->idMapa          = $mapa->getId();
            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao     = $idExpedicao;
            $pesoTotal = 0;
            $cubagemTotal = 0;

            $this->AddPage();

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $cargasSelecionadas = $this->getCargasSelecionadas();
            if (empty($cargasSelecionadas)) {
                $cargas = $expedicaoRepo->getCodCargasExterno($this->idExpedicao);
                $stringCargas = null;
                foreach ($cargas as $key => $carga) {
                    unset($carga['sequencia']);
                    if ($key >= 1) {
                        $stringCargas .= ',';
                    }
                    $stringCargas .= implode(',', $carga);
                }
            } else {
                if (is_array($cargasSelecionadas)) {
                    $stringCargas = implode(',', $cargasSelecionadas);
                } else {
                    $stringCargas = $cargasSelecionadas;
                }
            }

            $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());

            //Select Arial bold 8
            $this->SetFont('Arial','B',10);
            $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1,"C");
            $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
            $this->Cell(20, 3, "", 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, utf8_decode( $this->idExpedicao) . ' - CARGAS: ' . $stringCargas, 0, 1);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, '', 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

            $this->Image($imgCodBarras, 150 , 3, 50);
            $this->Cell(20, 4, "", 0, 1);
            $this->SetFont('Arial', 'B', 8);

            if ($tipoQebra == true) {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(17, 5, utf8_decode("Cod.Prod.") ,1, 0);
                $this->Cell(85, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                $this->Cell(15, 5, utf8_decode("Refer.") ,1, 0);
                $this->Cell(12, 5, utf8_decode("Qtd.") ,1, 0);
                $this->Cell(17, 5, utf8_decode("Caixas") ,1, 1);
            } else {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
                $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                $this->Cell(15, 5, utf8_decode("Refer.") ,1, 0);
                $this->Cell(12, 5, utf8_decode("Quant.") ,1, 1);
            }

            $this->Cell(20, 1, "", 0, 1);

            $total = 0;
            foreach ($produtos as $produto) {
                $this->SetFont('Arial', null, 8);
                $embalagemEn = $embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
                $pesoProduto = $pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

                $endereco     = $produto->getCodDepositoEndereco();
                $codProduto   = $produto->getCodProduto();
                $descricao    = utf8_decode($produto->getProduto()->getDescricao());
                $referencia   = $produto->getProduto()->getReferencia();
                $quantidade   = $produto->getQtdSeparar();
                $caixas       = $produto->getNumCaixaInicio().' - '.$produto->getNumCaixaFim();
                $dscEndereco  = "";
                $codigoBarras = '';
                if ($endereco != null)
                    $dscEndereco  = $endereco->getDescricao();
                if (isset($embalagemEn) && !empty($embalagemEn))
                    $codigoBarras = $embalagemEn->getCodigoBarras();
                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                    $cubagemTotal += $pesoProduto->getCubagem() * $quantidade;
                }

                if ($tipoQebra == true) {
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(17, 4, $codProduto ,0, 0);
                    $this->Cell(85, 4, substr($descricao,0,45) ,0, 0);
                    $this->Cell(30, 4, $codigoBarras, 0, 0);
                    $this->Cell(15, 4, $referencia ,0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade ,0, 0);
                    $this->Cell(15, 4, $caixas ,0, 1, 'C');
                } else {
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(20, 4, $codProduto ,0, 0);
                    $this->Cell(100, 4, substr($descricao,0,57) ,0, 0);
                    $this->Cell(30, 4, $codigoBarras, 0, 0);
                    $this->Cell(15, 4, $referencia ,0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade ,0, 1, 'C');
                }
                $this->SetFont('Arial', null, 8);
                $total += $quantidade;
                $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);

            }

            //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

            $this->total = $total;
            $this->imgCodBarras = $imgCodBarras;
            $this->cubagemTotal = $cubagemTotal;
            $this->pesoTotal = $pesoTotal;
            $this->mapa = $mapa;

            $this->InFooter = true;
            $pageSizeA4 = $this->_getpagesize();
            $wPage = $pageSizeA4[0]/12;

            $this->SetY(-23);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
            $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

            $this->SetFont('Arial','B',9);
            $this->Cell($wPage * 3, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
            $this->Cell($wPage * 2.5, 6, utf8_decode("EXPEDIÇÃO: " .$this->idExpedicao), 0, 0);
            $this->Cell($wPage * 3, 6, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1);
            $this->Cell($wPage * 3, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
            $this->Cell($wPage * 3, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

            $this->Image($this->imgCodBarras, 143, 280, 50);
            $this->InFooter = false;

        }
        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $expedicaoRepo->find($idExpedicao);
        if ($ExpedicaoEntity->getCodStatus() == Expedicao::STATUS_INTEGRADO) {
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
            $ExpedicaoEntity->setStatus($statusEntity);
            $em->persist($ExpedicaoEntity);
        }

        $this->Output('Mapa Separação-'.$idExpedicao.'.pdf','D');

        $em->flush();
        $em->clear();

    }

    private function layoutModelo3($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        if ($codBarras == null) {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('expedicao' => $idExpedicao, 'codStatus' => $status));
        } else {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->getMapaSeparacaoById($codBarras);
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');

        $pesoProdutoRepo = $em->getRepository('wms:Produto\Peso');
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $expedicaoRepo            = $em->getRepository('wms:Expedicao');

        foreach ($mapaSeparacao as $mapa) {

            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
            $tipoQebra = false;
            if (isset($mapaQuebra) && !empty($mapaQuebra))
                $tipoQebra = true;

            $produtos        = $mapaSeparacaoProdutoRepo->getMapaProduto($mapa->getId());


            $quebras = $mapa->getDscQuebra();

            if ($mapa->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
                $em->persist($mapa);
            }

            $this->idMapa          = $mapa->getId();
            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao     = $idExpedicao;
            $pesoTotal = 0;
            $cubagemTotal = 0;

            $this->AddPage();

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $cargasSelecionadas = $this->getCargasSelecionadas();
            if (empty($cargasSelecionadas)) {
                $cargas = $expedicaoRepo->getCodCargasExterno($this->idExpedicao);
                $stringCargas = null;
                foreach ($cargas as $key => $carga) {
                    unset($carga['sequencia']);
                    if ($key >= 1) {
                        $stringCargas .= ',';
                    }
                    $stringCargas .= implode(',', $carga);
                }
            } else {
                if (is_array($cargasSelecionadas)) {
                    $stringCargas = implode(',', $cargasSelecionadas);
                } else {
                    $stringCargas = $cargasSelecionadas;
                }
            }

            $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());

            //Select Arial bold 8
            $this->SetFont('Arial','B',10);
            $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1,"C");
            $this->Cell(20, 1, "_______________________________________________________________________________________________", 0, 1);
            $this->Cell(20, 3, "", 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, utf8_decode( $this->idExpedicao) . ' - CARGAS: ' . $stringCargas, 0, 1);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, '', 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

            $this->Image($imgCodBarras, 150 , 3, 50);
            $this->Cell(20, 4, "", 0, 1);
            $this->SetFont('Arial', 'B', 8);

            if ($tipoQebra == true) {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(17, 5, utf8_decode("Cod.Prod.") ,1, 0);
                $this->Cell(85, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                $this->Cell(15, 5, utf8_decode("Refer.") ,1, 0);
                $this->Cell(12, 5, utf8_decode("Qtd.") ,1, 0);
                $this->Cell(17, 5, utf8_decode("Caixas") ,1, 1);
            } else {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
                $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                $this->Cell(15, 5, utf8_decode("Refer.") ,1, 0);
                $this->Cell(12, 5, utf8_decode("Quant.") ,1, 1);
            }

            $this->Cell(20, 1, "", 0, 1);

            $total = 0;
            foreach ($produtos as $produto) {
                $this->SetFont('Arial', null, 8);
                $embalagemEn = $embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
                $pesoProduto = $pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

                $endereco     = $produto->getCodDepositoEndereco();
                $codProduto   = $produto->getCodProduto();
                $descricao    = utf8_decode($produto->getProduto()->getDescricao());
                $referencia   = $produto->getProduto()->getReferencia();
                $quantidade   = $produto->getQtdSeparar();
                $caixas       = $produto->getNumCaixaInicio().' - '.$produto->getNumCaixaFim();
                $dscEndereco  = "";
                $codigoBarras = '';
                if ($endereco != null)
                    $dscEndereco  = $endereco->getDescricao();
                if (isset($embalagemEn) && !empty($embalagemEn))
                    $codigoBarras = $embalagemEn->getCodigoBarras();
                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                    $cubagemTotal += $pesoProduto->getCubagem() * $quantidade;
                }

                if ($tipoQebra == true) {
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(17, 4, $codProduto ,0, 0);
                    $this->Cell(85, 4, substr($descricao,0,45) ,0, 0);
                    $this->Cell(30, 4, $codigoBarras, 0, 0);
                    $this->Cell(15, 4, $referencia ,0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade ,0, 0);
                    $this->Cell(15, 4, $caixas ,0, 1, 'C');
                } else {
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(20, 4, $codProduto ,0, 0);
                    $this->Cell(100, 4, substr($descricao,0,57) ,0, 0);
                    $this->Cell(30, 4, $codigoBarras, 0, 0);
                    $this->Cell(15, 4, $referencia ,0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(15, 4, $quantidade ,0, 1, 'C');
                }
                $this->SetFont('Arial', null, 8);
                $total += $quantidade;
                $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);

            }

            //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

            $this->total = $total;
            $this->imgCodBarras = $imgCodBarras;
            $this->cubagemTotal = $cubagemTotal;
            $this->pesoTotal = $pesoTotal;
            $this->mapa = $mapa;

            $this->InFooter = true;
            $pageSizeA4 = $this->_getpagesize();
            $wPage = $pageSizeA4[0]/12;

            $this->SetY(-23);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 6, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(120, 6, utf8_decode($this->quebrasEtiqueta), 0, 0);
            $this->Cell($wPage * 11, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);

            $this->SetFont('Arial','B',9);
            $this->Cell($wPage * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
            $this->Cell($wPage * 4, 6, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1);
            $this->Cell($wPage * 4, 6, utf8_decode("CUBAGEM TOTAL " . $this->cubagemTotal), 0, 0);
            $this->Cell($wPage * 4, 6, utf8_decode("PESO TOTAL " . $this->pesoTotal), 0, 1);

            $this->Image($this->imgCodBarras, 143, 280, 50);
            $this->InFooter = false;

        }
        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $expedicaoRepo->find($idExpedicao);
        if ($ExpedicaoEntity->getCodStatus() == EXPEDICAO::STATUS_INTEGRADO) {
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
            $ExpedicaoEntity->setStatus($statusEntity);
            $em->persist($ExpedicaoEntity);
        }

        $this->Output('Mapa Separação-'.$idExpedicao.'.pdf','D');

        $em->flush();
        $em->clear();

    }

    private function layoutModelo4($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        if ($codBarras == null) {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('expedicao' => $idExpedicao, 'codStatus' => $status));
        } else {
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->getMapaSeparacaoById($codBarras);
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $pesoProdutoRepo = $em->getRepository('wms:Produto\DadoLogistico');
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $expedicaoRepo            = $em->getRepository('wms:Expedicao');

        foreach ($mapaSeparacao as $mapa) {

            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
            $tipoQebra = false;
            if (isset($mapaQuebra) && !empty($mapaQuebra))
                $tipoQebra = true;

            $produtos        = $mapaSeparacaoProdutoRepo->getMapaProduto($mapa->getId());


            $quebras = $mapa->getDscQuebra();

            if ($mapa->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO) {
                $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
                $em->persist($mapa);
            }

            $this->idMapa          = $mapa->getId();
            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao     = $idExpedicao;
            $pesoTotal = 0;
            $cubagemTotal = 0;

            $this->AddPage();

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $cargasSelecionadas = $this->getCargasSelecionadas();
            if (empty($cargasSelecionadas)) {
                $cargas = $expedicaoRepo->getCodCargasExterno($this->idExpedicao);
                $stringCargas = null;
                foreach ($cargas as $key => $carga) {
                    unset($carga['sequencia']);
                    if ($key >= 1) {
                        $stringCargas .= ',';
                    }
                    $stringCargas .= implode(',', $carga);
                }
            } else {
                if (is_array($cargasSelecionadas)) {
                    $stringCargas = implode(',', $cargasSelecionadas);
                } else {
                    $stringCargas = $cargasSelecionadas;
                }
            }

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
            $pedidoRepo = $em->getRepository('wms:Expedicao\Pedido');
            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
            $cargaRepo = $em->getRepository('wms:Expedicao\Carga');
            $codCargaExterno = explode(',',$stringCargas);
            $cargaEn = $cargaRepo->findOneBy(array('codCargaExterno' => array($codCargaExterno[0])));
            $pedidoEn = $pedidoRepo->findOneBy(array('carga' => $cargaEn->getId()));
            $linhaSeparacao = '';
            if (isset($pedidoEn) && !empty($pedidoEn))
                $linhaSeparacao = $pedidoEn->getItinerario()->getDescricao();

            $imgCodBarras = @CodigoBarras::gerarNovo($mapa->getId());

            $this->Cell(20, 1, "", 0, 1);

            $total = 0;
            $ruaAnterior = 99999;
            foreach ($produtos as $produto) {
                $this->SetFont('Arial', null, 8);

                $embalagemEn  = $produto->getProdutoEmbalagem();
                $rua          = null;
                $endereco     = $produto->getCodDepositoEndereco();
                $codProduto   = $produto->getCodProduto();
                $descricao    = utf8_decode($produto->getProduto()->getDescricao());
                $quantidade   = $produto->getQtdSeparar();
                $caixaInicio  = $produto->getNumCaixaInicio();
                $caixaFim     = $produto->getNumCaixaFim();
                $pesoProduto  = $pesoProdutoRepo->findOneBy(array('embalagem' => $embalagemEn));

                $caixas       = $caixaInicio.' - '.$caixaFim;
                $dscEndereco  = '';
                $codigoBarras = '';
                if ($endereco != null) {
                    $dscEndereco = $endereco->getDescricao();
                    $rua = $endereco->getRua();
                }
                if (isset($embalagemEn) && !empty($embalagemEn))
                    $codigoBarras = $embalagemEn->getCodigoBarras();
                    $embalagem   = $embalagemEn->getDescricao() . ' (' . $embalagemEn->getQuantidade() . ')';
                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $pesoTotal += ($pesoProduto->getPeso() * $quantidade);
                    $cubagemTotal += $pesoProduto->getCubagem() * $quantidade;
                }
                if ($ruaAnterior != $rua) {
                    $this->Cell(20, 7, "", 0, 1);
                    $this->SetFont('Arial','B',10);
                    $this->Cell(24, 2, utf8_decode("EXPEDIÇÃO: "), 0, 0);
                    $this->SetFont('Arial',null,10);
                    $this->Cell(4, 2, utf8_decode( $this->idExpedicao) . ' - CARGAS: ' . $stringCargas, 0, 1);

                    $this->SetFont('Arial',null,10);
                    $this->Cell(4, 2, '', 0, 1);
                    $this->SetFont('Arial','B',10);
                    $this->Cell(20, 2, utf8_decode("QUEBRAS: "), 0, 0);
                    $this->SetFont('Arial',null,10);
                    $this->Cell(20, 2, utf8_decode($this->quebrasEtiqueta), 0, 1);

                    $this->SetFont('Arial',null,10);
                    $this->Cell(4, 2, '', 0, 1);
                    $this->SetFont('Arial','B',10);
//                    $this->Cell(20, 2, utf8_decode("ROTA: "), 0, 0);
                    $this->SetFont('Arial',null,10);
//                    $this->Cell(20, 2, utf8_decode($linhaSeparacao), 0, 1);

                    $this->SetFont('Arial',null,10);
                    $this->Cell(4, 2, '', 0, 1);
                    $this->SetFont('Arial','B',10);
                    $this->Cell(20, 2, utf8_decode("RUA: "), 0, 0);
                    $this->SetFont('Arial',null,10);
                    $this->Cell(20, 2, utf8_decode($rua), 0, 1);

                    $this->SetFont('Arial',null,10);
                    $this->Cell(4, 2, '', 0, 1);
                    $this->SetFont('Arial','B',10);
                    $this->Cell(20, 2, utf8_decode("PLACA: "), 0, 0);
                    $this->SetFont('Arial',null,10);
                    $this->Cell(20, 2, utf8_decode($cargaEn->getPlacaCarga()), 0, 1);

                    $this->Cell(20, 4, "", 0, 1);
                    $this->SetFont('Arial', 'B', 8);

                    if ($tipoQebra == true) {
                        $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                        $this->Cell(17, 5, utf8_decode("Cod.Prod.") ,1, 0);
                        $this->Cell(80, 5, utf8_decode("Produto") ,1, 0);
                        $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                        $this->Cell(12, 5, utf8_decode("Qtd.") ,1, 0);
                        $this->Cell(15, 5, utf8_decode("Emb.:") ,1, 0);
                        $this->Cell(17, 5, utf8_decode("Caixas") ,1, 1);
                    } else {
                        $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                        $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
                        $this->Cell(90, 5, utf8_decode("Produto") ,1, 0);
                        $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
                        $this->Cell(15, 5, utf8_decode("Quant.") ,1, 0);
                        $this->Cell(18, 5, utf8_decode("Emb.:") ,1, 1);
                    }

                    $this->Cell(20, 1, "", 0, 1);

                }

                if ($tipoQebra == true) {
                    $this->SetFont('Arial', "", 8);
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(17, 4, $codProduto ,0, 0);
                    $this->Cell(80, 4, substr($descricao,0,45) ,0, 0);
                    $this->Cell(30, 4, $codigoBarras, 0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(12, 4, $quantidade ,0, 0);
                    $this->SetFont('Arial', '', 10);
                    $this->Cell(16, 4, $embalagem ,0, 0);
                    $this->Cell(15, 4, $caixas ,0, 1, 'C');
                } else {
                    $this->SetFont('Arial', "", 8);
                    $this->Cell(20, 4, $dscEndereco ,0, 0);
                    $this->Cell(20, 4, $codProduto ,0, 0);
                    $this->Cell(90, 4, substr($descricao,0,57) ,0, 0);
                    $this->Cell(25, 4, $codigoBarras, 0, 0);
                    $this->SetFont('Arial', "B", 10);
                    $this->Cell(20, 4, $quantidade ,0, 0, 'C');
                    $this->SetFont('Arial', '', 10);
                    $this->Cell(12, 4, $embalagem ,0, 1);
                }
                $ruaAnterior = $rua;
                $this->SetFont('Arial', null, 8);
                $total += $quantidade;
                $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);

            }

            //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - TOTAL À SEPARAR ==> $total"), 0, 1);

            $this->total = $total;
            $this->imgCodBarras = $imgCodBarras;
            $this->cubagemTotal = $cubagemTotal;
            $this->pesoTotal = $pesoTotal;
            $this->mapa = $mapa;

            $this->InFooter = true;
//            $pageSizeA4 = $this->_getpagesize('A4');
            $wPage = 595.28/12;

            $this->SetY(-23);
            $this->SetFont('Arial','B',9);
            $this->Cell(16 * 4, 6, utf8_decode("QUEBRAS: ".substr($this->quebrasEtiqueta,0,23)), 0, 0);
            $this->Cell(14 * 4, 6, utf8_decode("EXPEDICAO " . $this->idExpedicao), 0, 0);
            $this->Cell(10 * 4, 6, utf8_decode("TOTAL À SEPARAR : $this->total"), 0, 1);
            $this->Cell(16 * 4, 6, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 0);
            $this->Cell(10 * 4, 6, utf8_decode("CARREGAMENTO " . $stringCargas), 0, 1);
            $this->Cell(16 * 4, 6, utf8_decode("ROTA: " . $linhaSeparacao), 0, 0);
            $this->Cell(10 * 4, 6, utf8_decode('PESO TOTAL ' . number_format($this->pesoTotal,3,',','') . 'kg'), 0, 1);
            $this->Image($this->imgCodBarras, 143, 280, 50);

            $this->InFooter = false;

        }

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $produtos = $mapaSeparacaoProdutoRepo->getMapaProdutoByExpedicao($idExpedicao);

        if (!empty($produtos)) {
            $this->AddPage();
            //Select Arial bold 8
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 10, utf8_decode("RELATÓRIO DE CODIGO DE BARRAS DE PRODUTOS DA EXPEDIÇÃO ". $this->idExpedicao), 0, 1);

            $x = 170;
            $y = 30;
            $count = 1;
            foreach ($produtos as $produto)
            {
                $height   = 8;
                $angle    = 0;
                $type     = 'code128';
                $black    = '000000';

                if($count > 12){
                    $this->AddPage();
                    $count = 1;
                    $y = 30;
                }

                $this->SetFont('Arial','',10);
                $this->Cell(15, 20, $produto['id'], 0, 0);
                $this->Cell(90, 20, substr($produto['descricao'],0,40), 0, 0);
                $this->Cell(90, 20, $produto['unidadeMedida'], 0, 1);

                $data = Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>$produto['codigoBarras']),0.5,10);
                $len = $this->GetStringWidth($data['hri']);
                $this->Text(($x-$height) + (($height - $len)/2) + 3, $y + 8,$produto['codigoBarras']);
                $y = $y + 20;
                $count++;
            }
        }

        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $expedicaoRepo->find($idExpedicao);
        if ($ExpedicaoEntity->getCodStatus() == EXPEDICAO::STATUS_INTEGRADO) {
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
            $ExpedicaoEntity->setStatus($statusEntity);
            $em->persist($ExpedicaoEntity);
        }

        $this->Output('Mapa Separação-'.$idExpedicao.'.pdf','D');

        $em->flush();
        $em->clear();

    }

    /**
     * @return mixed
     */
    public function getCargasSelecionadas()
    {
        return $this->cargasSelecionadas;
    }

    /**
     * @param mixed $cargasSelecionadas
     */
    public function setCargasSelecionadas($cargasSelecionadas)
    {
        $this->cargasSelecionadas = $cargasSelecionadas;
    }

    public function Header()
    {
    }

    public function Footer()
    {
    }
}
