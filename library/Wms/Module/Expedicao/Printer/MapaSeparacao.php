<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;
use Wms\Util\Barcode\Barcode;

class MapaSeparacao extends Pdf
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
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('id' => $codBarras));
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);


        foreach ($mapaSeparacao as $mapa) {
            $produtos        = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto')->getMapaProduto($mapa->getId());
            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => 'T'));
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

            //Select Arial bold 8
            $this->SetFont('Arial','B',10);
            $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1,"C");
            $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
            $this->Cell(20, 3, "", 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(24, 4, utf8_decode("EXPEDIÇÃO: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, utf8_decode( $this->idExpedicao) . ' - CARGAS: ' . $stringCargas, 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);
            $this->Cell(20, 4, "", 0, 1);

            $this->SetFont('Arial', 'B', 8);
            if ($tipoQebra == true) {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
                $this->Cell(90, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(30, 5, utf8_decode("Embalagem") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Quantidade") ,1, 0);
                $this->Cell(15, 5, utf8_decode("Caixas") ,1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }else {
                $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
                $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
                $this->Cell(35, 5, utf8_decode("Embalagem") ,1, 0);
                $this->Cell(20, 5, utf8_decode("Quantidade") ,1, 1);
                $this->Cell(20, 1, "", 0, 1);
            }

            foreach ($produtos as $produto) {
                $dscEndereco = "";
                $embalagem   = $produto->getProdutoEmbalagem();
                $codProduto  = $produto->getCodProduto();
                $descricao   = utf8_decode($produto->getProduto()->getDescricao());
                $embalagem   = $embalagem->getDescricao() . ' (' . $embalagem->getQuantidade() . ')';
                $quantidade  = $produto->getQtdSeparar();
                $caixas      = $produto->getNumCaixaInicio().' - '.$produto->getNumCaixaFim();
                $endereco    = $produto->getCodDepositoEndereco();
                if ($endereco != null)
                    $dscEndereco = $endereco->getDescricao();

                $this->SetFont('Arial',  null, 8);
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
            $mapaSeparacao = $em->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('id' => $codBarras));
        }
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');

        $pesoProdutoRepo = $em->getRepository('wms:Produto\Peso');
        $mapaSeparacaoProdutoRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $expedicaoRepo            = $em->getRepository('wms:Expedicao');

        foreach ($mapaSeparacao as $mapa) {

            $mapaQuebra      = $em->getRepository('wms:Expedicao\MapaSeparacaoQuebra')->findOneBy(array('mapaSeparacao' => $mapa, 'tipoQuebra' => 'T'));
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
            $pageSizeA4 = $this->_getpagesize('A4');
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
