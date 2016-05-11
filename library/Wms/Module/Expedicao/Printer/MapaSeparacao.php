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

    public function Header()
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(200, 3, utf8_decode("MAPA DE SEPARAÇÃO "), 0, 1,"C");
        $this->Cell(20, 1, "__________________________________________________________________________________________________", 0, 1);
        $this->Cell(20, 3, "", 0, 1);
        $this->SetFont('Arial','B',10);
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $em->getRepository('wms:Expedicao');
        $cargas = $expedicaoRepo->getCodCargasExterno($this->idExpedicao);
        $stringCargas = null;
        foreach ($cargas as $key => $carga) {
            unset($carga['sequencia']);
            $stringCargas .= implode(',', $carga);
        }
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
//        $this->Cell(20, 5, utf8_decode("Grade") ,1, 0);
        $this->Cell(100, 5, utf8_decode("Produto") ,1, 0);
        $this->Cell(35, 5, utf8_decode("Embalagem") ,1, 0);
        $this->Cell(20, 5, utf8_decode("Quantidade") ,1, 1);
        $this->Cell(20, 1, "", 0, 1);
    }


    public function Footer()
    {
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


    public function imprimir($idExpedicao, $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $codBarras = null)
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

            $this->quebrasEtiqueta = $quebras;
            $this->idExpedicao = $idExpedicao;

            $this->AddPage();
            $this->idMapa = $mapa->getId();
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
}
