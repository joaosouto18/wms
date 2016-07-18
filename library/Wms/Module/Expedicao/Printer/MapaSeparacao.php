<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;

class MapaSeparacao extends Pdf
{
    private $idMapa;
    private $idExpedicao;
    private $quebrasEtiqueta;
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
            $produtos = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto')->findBy(array('mapaSeparacao'=>$mapa->getId()));
            $quebras = $mapa->getDscQuebra();
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
            $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
            $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
            $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
            $this->Cell(35, 5, utf8_decode("Embalagem") ,1, 0);
            $this->Cell(20, 5, utf8_decode("Quantidade") ,1, 1);
            $this->Cell(20, 1, "", 0, 1);

            foreach ($produtos as $produto) {
                $this->SetFont('Arial',  null, 8);
                //$endereco = $produto->getProdutoEmbalagem()->getEndereco();

                $endereco = $produto->getCodDepositoEndereco();
                $dscEndereco = "";
                if ($endereco != null) {
                    $dscEndereco = $endereco->getDescricao();
                }
                $embalagem = $produto->getProdutoEmbalagem();
                $this->Cell(20, 4, utf8_decode($dscEndereco) ,0, 0);
                $this->Cell(20, 4, utf8_decode($produto->getCodProduto()) ,0, 0);
//                $this->Cell(20, 4, utf8_decode($produto->getDscGrade()) ,0, 0);
                $this->Cell(100, 4, substr(utf8_decode($produto->getProduto()->getDescricao()),0,54) ,0, 0);
                $this->Cell(35, 4, utf8_decode($embalagem->getDescricao() . " (". $embalagem->getQuantidade() . ")") ,0, 0);
                $this->Cell(20, 4, utf8_decode($produto->getQtdSeparar()) ,0, 1, 'C');
                $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                $this->Cell(20, 1, "", 0, 1);
            }

            $this->SetFont('Arial',null,10);
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->SetFont('Arial','B',9);

            $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $this->idMapa), 0, 1);
            $this->SetFont('Arial','B',7);
            //Go to 1.5 cm from bottom
            $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");

            //$this->SetY(-92);
            $this->Image(@CodigoBarras::gerarNovo($this->idMapa), 150, 280, 50);

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


        foreach ($mapaSeparacao as $mapa) {
            $produtos        = $em->getRepository('wms:Expedicao\MapaSeparacaoProduto')
                ->getMapaProduto($mapa->getId());
            $pesoProdutoRepo = $em->getRepository('wms:Produto\Peso');
            $pedidoProduto = $produtos[0]->getPedidoProduto();
            $carga = '';
            if (isset($pedidoProduto) && !empty($pedidoProduto))
                $carga = $produtos[0]->getPedidoProduto()->getPedido()->getCarga()->getCodCargaExterno();

            $quebras = $mapa->getDscQuebra();
            $mapa->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
            $em->persist($mapa);

            $this->idMapa = $mapa->getId();
            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao = $idExpedicao;
            $pesoTotal = 0;
            $cubagemTotal = 0;

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
            $this->SetFont('Arial',null,10);
            $this->Cell(4, 4, '', 0, 1);
            $this->SetFont('Arial','B',10);
            $this->Cell(20, 4, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 4, utf8_decode($this->quebrasEtiqueta), 0, 1);

            $this->Cell(20, 4, "", 0, 1);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(20, 5, utf8_decode("Endereço") ,1, 0);
            $this->Cell(20, 5, utf8_decode("Cod.Produto") ,1, 0);
            $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
            $this->Cell(30, 5, utf8_decode("Cod. Barras") ,1, 0);
            $this->Cell(15, 5, utf8_decode("Refer.") ,1, 0);
//            $this->Cell(20, 5, utf8_decode("Embalagem") ,1, 0);
            $this->Cell(12, 5, utf8_decode("Quant.") ,1, 1);
            $this->Cell(20, 1, "", 0, 1);

            foreach ($produtos as $produto) {
                $this->SetFont('Arial', null, 8);
                //$endereco = $produto->getProdutoEmbalagem()->getEndereco();

                $pesoProduto = $pesoProdutoRepo->findOneBy(array('produto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade()));

                $endereco = $produto->getCodDepositoEndereco();
                $dscEndereco = "";
                if ($endereco != null) {
                    $dscEndereco = $endereco->getDescricao();
                }
                $embalagemEn = $embalagemRepo->findOneBy(array('codProduto' => $produto->getProduto()->getId(), 'grade' => $produto->getProduto()->getGrade(), 'isPadrao' => 'S'));
                $this->Cell(20, 4, utf8_decode($dscEndereco) ,0, 0);
                $this->Cell(20, 4, utf8_decode($produto->getCodProduto()) ,0, 0);
                $this->Cell(100, 4, substr(utf8_decode($produto->getProduto()->getDescricao()),0,57) ,0, 0);
                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $pesoTotal = $pesoTotal + $pesoProduto->getPeso();
                    $cubagemTotal = $cubagemTotal + $pesoProduto->getCubagem();
                }
                $codigoBarras = '';
                if (isset($embalagemEn) && !empty($embalagemEn))
                    $codigoBarras = $embalagemEn->getCodigoBarras();


                $this->Cell(30, 4, $codigoBarras, 0, 0);
                $this->Cell(15, 4, utf8_decode($produto->getProduto()->getReferencia()) ,0, 0);
//                $this->Cell(20, 4, utf8_decode($embalagem->getDescricao() . " (". $embalagem->getQuantidade() . ")") ,0, 0);
                $this->Cell(15, 4, utf8_decode($produto->getQtdSeparar()) ,0, 1, 'C');
                $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
                $this->Cell(20, 1, "", 0, 1);
            }

            //FOOTER PASSADO PARA ESSA LINHA ADIANTE DEVIDO PROBLEMAS COM O CODIGO DE BARRAS DO NUMERO DO MAPA
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 1, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 0, 1);
            $this->SetFont('Arial','B',9);

            $this->Cell(4, 10, utf8_decode("PESO TOTAL " . $pesoTotal), 0, 1);
            $this->Cell(4, 10, utf8_decode("CUBAGEM TOTAL " . $cubagemTotal), 0, 1);
            $this->Cell(4, 10, utf8_decode("MAPA DE SEPARAÇÃO " . $mapa->getId()), 0, 1);
            $this->SetFont('Arial','B',7);
            //Go to 1.5 cm from bottom
            $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");

            $this->SetFont('Arial','B',10);
            $this->SetY(255);
            $this->Cell(20, 20, utf8_decode("QUEBRAS: "), 0, 0);
            $this->SetFont('Arial',null,10);
            $this->Cell(20, 20, utf8_decode($this->quebrasEtiqueta), 0, 1);

            //$this->SetY(-92);
            $this->Image(@CodigoBarras::gerarNovo($mapa->getId()), 150, 280, 50);

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
