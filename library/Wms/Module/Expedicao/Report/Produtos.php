<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf,
    Wms\Domain\Entity\Expedicao\VRelProdutosRepository;

class Produtos extends Pdf
{
    protected $idExpedicao;
    protected $placaExpedicao;
    protected $dataInicio;
    protected $cargas;
    protected $title;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $_em;

    public function Header()
    {
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 10, utf8_decode($this->title . $this->idExpedicao . " PLACA:" . $this->placaExpedicao. " DATA:". $this->dataInicio . $this->cargas), 0, 1);
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

    public function setHeader ($idExpedicao, $cargas) {
        $this->_em = \Zend_Registry::get('doctrine')->getEntityManager();

        if (strrpos($idExpedicao,",") == false) {
            $repoExpedicao = $this->_em->getRepository("wms:Expedicao");

            /** @var \Wms\Domain\Entity\Expedicao $enExpedicao */
            $enExpedicao = $repoExpedicao->find($idExpedicao);
            $this->idExpedicao = $enExpedicao->getId();
            $this->placaExpedicao = $enExpedicao->getPlacaExpedicao();
            $this->dataInicio = $enExpedicao->getDataInicio()->format("d/m/Y");
        } else{
            $this->idExpedicao = $idExpedicao;
            $this->placaExpedicao = "";
            $this->dataInicio = "";
        }


        if (is_null($cargas)) {
            $this->cargas = null;
        } else {
            $this->cargas = " CARGAS: " . implode(',', $cargas);
        }
    }

    public function imprimirSemDados ($idExpedicao, $produtos, $central, $cargas = null, $modelo = 1)
    {
        $this->title = 'RELATÓRIO DE PRODUTOS SEM ETIQUETAS DA EXPEDIÇÃO ';
        $this->setHeader($idExpedicao, $cargas);
        $this->layout($modelo);
        $this->formataProduto($produtos,$modelo);
        $this->Output('Produtos-SemEtiquetas-Exp-'.$idExpedicao.'-'.$central.'.pdf','D');
    }

    public function layout($modelo)
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 5, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->AddPage();

        switch($modelo){
            case 2:
                $this->Cell(20, 5, "Produto", 1);
                $this->Cell(87, 5, utf8_decode("Descrição"), 1);
                $this->Cell(30, 5, "Mapa", 1);
                $this->Cell(5, 5, "X", 1);
                $this->Cell(12, 5, "Quant.", 1);
                $this->Cell(23, 5, "Fabricante", 1);
                $this->Cell(15, 5, "Peso", 1);
                $this->Cell(28, 5, "Larg x Alt x Comp", 1);
                $this->Cell(65, 5, utf8_decode("Descrição/Anotação"), 1);
                $this->Ln();
                break;
            default:
                $this->Cell(20, 5, "Produto", 1);
                $this->Cell(75, 5, utf8_decode("Descrição"), 1);
                $this->Cell(22, 5, "Mapa", 1);
                $this->Cell(20, 5, "Grade", 1);
                $this->Cell(5, 5, "X", 1);
                $this->Cell(12, 5, "Quant.", 1);
                $this->Cell(23, 5, "Fabricante", 1);
                $this->Cell(15, 5, "Peso", 1);
                $this->Cell(28, 5, "Larg x Alt x Comp", 1);
                $this->Cell(65, 5, utf8_decode("Descrição/Anotação"), 1);
                $this->Ln();
                break;
        }
    }



    /**
     * @param $idExpedicao
     * @param $central
     */
    public function imprimir($idExpedicao, $central, $cargas, $linhaSeparacao = Null, $modelo)
    {
        $this->title = 'RELATÓRIO DE PRODUTOS DA EXPEDIÇÃO ';
        $this->setHeader($idExpedicao, $cargas);

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpRepo */
        $ExpRepo   = $this->_em->getRepository('wms:Expedicao');
        $produtos = $ExpRepo->getProdutos($idExpedicao, $central, $cargas, $linhaSeparacao);

        $this->layout($modelo);
        $this->formataProduto($produtos,$modelo);

        $this->Output('Produtos-Expedicao.pdf','D');
    }


    public function getQuantidade($produtos) {
        $gradeAnterior = null;
        $prodAnterior = null;
        $embalagemAnterior = null;
        $arrayCargas = array();
        $arrayQtd = array();
        $mapaAnterior = null;

        $qtdgradeAnterior = null;
        $qtdprodAnterior = null;
        $qtdArrayProduto = null;
        $addQuantidade = null;
        $qtdEmbalagemAnterior = null;
        $qtdIndPadrao = null;

        foreach ($produtos as $key => $produto) {

            $carga = 'Carga: ' . $produto['codCargaExterno'] . ' / ' . $produto['dscLinhaEntrega'] . ' - ' . $produto['dscItinerario'] . ' - ' . $produto['codItinerario'];

            if (($prodAnterior != $produto->getCodProduto()) OR ($gradeAnterior != $produto->getGrade())) {
                $arrayCargas = array();
                $arrayQtd = array();
                if (!isset($qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()])) {
                    $qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()] = null;
                }
            }

            if (!in_array($carga, $arrayCargas)) {
                $arrayCargas[] = $carga;
                $arrayQtd[] = $produto->getQuantidade();
            }

            if (($produto == end($produtos)) || ($produtos[$key + 1]->getCodProduto() != $produto->getCodProduto()) || ($produtos[$key + 1]->getGrade() != $produto->getGrade())  ) {
                foreach ($arrayCargas as $keyCarga => $tmpCarga) {
                    $qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()] = $arrayQtd[$keyCarga] + $qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()];
                }
            };

            $prodAnterior = $produto->getCodProduto();
            $gradeAnterior = $produto->getGrade();
        }

        return $qtdArrayProduto;

    }

    /**
     * @param $produtos
     */
    public function formataProduto($produtos, $modelo)
    {
        $gradeAnterior = null;
        $prodAnterior = null;
        $embalagemAnterior = null;
        $reentregaAnterior = null;
        $arrayCargas = array();
        $arrayQtd = array();
        $seqQuebra = null;

        $qtdArrayProduto = $this->getQuantidade($produtos);

        unset($produto);

        foreach ($produtos as $key => $produto) {

            if (is_numeric($produto->getCodItinerario())) {
                $codItinerario = "";
            } else {
                $codItinerario = " - " . $produto->getCodItinerario();
            }

            $carga = 'Carga: ' . $produto->getCodCargaExterno() . ' / ' . $produto->getDscLinhaEntrega() . ' - ' . $produto->getDscItinerario() . $codItinerario;
            $indPadrao = $produto->getIndPadrao();

            if (isset($seqQuebra) AND $seqQuebra <> $produto->getSeqQuebra()) {
                $this->AddPage();
            }

            if (($prodAnterior != $produto->getCodProduto()) OR ($gradeAnterior != $produto->getGrade()) || $reentregaAnterior != $produto['codReentrega']) {
                $embalagemAnterior = null;
                $arrayCargas = array();
                $arrayQtd = array();

                switch($modelo) {
                    case 2:
                        $this->Cell(15, 5, $produto->getCodProduto(), 1);
                        $this->Cell(87, 5, utf8_decode(substr($produto->getDescricao(), 0, 40)), 1);
                        $this->Cell(30, 5, utf8_decode($produto->getLinhaSeparacao()), 1);
                        $this->Cell(5, 5, " ", 1);
                        $this->Cell(12, 5, $qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()], 1);
                        $this->Cell(23, 5, utf8_decode($produto->getFabricante()), 1);
                        $this->Cell(108, 5, "", 1);
                        $this->Ln();
                        break;
                    default:
                        $this->Cell(15, 5, $produto->getCodProduto(), 1);
                        $this->Cell(75, 5, utf8_decode(substr($produto->getDescricao(), 0, 40)), 1);
                        $this->Cell(22, 5, utf8_decode($produto->getLinhaSeparacao()), 1);
                        $this->Cell(20, 5, utf8_decode($produto->getGrade()), 1);
                        $this->Cell(5, 5, " ", 1);
                        $this->Cell(12, 5, $qtdArrayProduto[$produto->getCodProduto()][$produto->getGrade()], 1);
                        $this->Cell(23, 5, utf8_decode($produto->getFabricante()), 1);
                        $this->Cell(108, 5, "", 1);
                        $this->Ln();
                        break;
                }
            }

            if (!in_array($carga, $arrayCargas)) {
                $arrayCargas[] = $carga;
                $arrayQtd[] = $produto->getQuantidade();
            }

            if ($indPadrao == 'S' || (!$embalagemAnterior && $indPadrao == 'N')) {
                if ($embalagemAnterior != $produto->getVolume()) {

                    $this->Cell(172, 5, "", 0);
                    $this->Cell(15, 5, $produto->getPeso(), "TB");
                    $this->Cell(28, 5, $produto->getLargura() . " x " . $produto->getAltura() . " x " . $produto->getProfundidade(), "TB");
                    $this->Cell(65, 5, $produto->getVolume(), "TB");
                    $this->Ln();
                }
                $embalagemAnterior = $produto->getVolume();
            }

            if (($produto == end($produtos)) || !(($produtos[$key + 1]->getCodProduto() == $produto->getCodProduto()) && ($produtos[$key + 1]->getGrade() == $produto->getGrade()))  ) {
                foreach ($arrayCargas as $keyCarga => $tmpCarga) {
                    $this->Cell(132, 5, "", 0);
                    $this->Cell(128, 5, $tmpCarga, "TB");
                    $this->Cell(20, 5, 'Qntde: ' . $arrayQtd[$keyCarga], "TB");
                    $this->Ln();
                }
            };

            $prodAnterior = $produto->getCodProduto();
            $reentregaAnterior = $produto['codReentrega'];
            $gradeAnterior = $produto->getGrade();
            $seqQuebra = $produto->getSeqQuebra();
        }
    }

}
