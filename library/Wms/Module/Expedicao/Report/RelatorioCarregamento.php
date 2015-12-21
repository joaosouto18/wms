<?php

namespace Wms\Module\Expedicao\Report;

use Core\Pdf;

class RelatorioCarregamento extends Pdf
{
    protected $idExpedicao;
    protected $placaExpedicao;
    protected $dataInicio;
    protected $cargasExpedicao;
    protected $linhasExpedicao;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $_em;

    public function Header()
    {
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 10, utf8_decode("RELATÓRIO DE CARREGAMENTO"), 0);

        $this->Ln();
        $this->SetFont('Arial','B',8);
        $this->Cell(12,4,utf8_decode("PLACA: "),0);
        $this->SetFont('Arial','',8);
        $this->Cell(25,4,utf8_decode($this->placaExpedicao),0);
        $this->SetFont('Arial','B',8);
        $this->Cell(24,4,utf8_decode("DATA DE INICIO: "),0);
        $this->SetFont('Arial','',8);
        $this->Cell(25,4,utf8_decode($this->dataInicio),0);
        $this->SetFont('Arial','B',8);
        $this->Cell(19,4,utf8_decode("EXPEDIÇÃO: "),0,0);
        $this->SetFont('Arial','',8);
        $this->Cell(25,4,utf8_decode($this->idExpedicao),0);

        $this->Ln();

        $this->SetFont('Arial','B',8);
        $this->Cell(14,4,utf8_decode("CARGAS: "),0,0);
        $this->SetFont('Arial','',8);
        $this->Cell(50,4,utf8_decode($this->cargasExpedicao) ,0,1);


        $this->SetFont('Arial','B',8);
        $this->Cell(37,4,utf8_decode( "LINHAS DE SEPARAÇÃO: "),0,0);
        $this->SetFont('Arial','',8);
        $this->Cell(50,4,utf8_decode($this->linhasExpedicao),0,1);

        $this->Ln();

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

    public function setHeader ($idExpedicao, $cargas, $linhas) {
        $repoExpedicao = $this->_em->getRepository("wms:Expedicao");

        /** @var \Wms\Domain\Entity\Expedicao $enExpedicao */
        $enExpedicao = $repoExpedicao->find($idExpedicao);
        $this->idExpedicao = $enExpedicao->getId();
        $this->placaExpedicao = $enExpedicao->getPlacaExpedicao();
        $this->dataInicio = $enExpedicao->getDataInicio()->format("d/m/Y");
        $this->cargasExpedicao = $cargas;
        $this->linhasExpedicao = $linhas;
    }

    public function layout()
    {
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 5, 0);
        $this->SetFont('Arial', 'B', 8);

    }

    public function imprimir($idExpedicao, $modelo = 1)
    {
        $this->_em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        $RelProdutos   = $this->_em->getRepository('wms:Expedicao\VRelProdutos');


        $todosProdutos = $RelProdutos->getProdutosByExpedicaoOrderByCarga($idExpedicao,NULL);
        $produtosEmbalados = $RelProdutos->getProdutosByExpedicaoOrderByCarga($idExpedicao,NULL,"S");
        $produtosNaoEmbalados = $RelProdutos->getProdutosByExpedicaoOrderByCarga($idExpedicao,NULL,"N");

        $cargas = $this->cargas($todosProdutos);
        $linhas = $this->linhas($todosProdutos);

        $this->setHeader($idExpedicao,$cargas,$linhas);
        $this->layout();
        if (count($produtosNaoEmbalados) > 0) {
            $this->AddPage();
            $this->formataProduto($produtosNaoEmbalados,false, $modelo);
        }
        if (count ($produtosEmbalados) > 0) {
            $this->AddPage();
            $this->formataProduto($produtosEmbalados,true, $modelo);
        }
        $this->Output('Relatório-Carregamento-'.$idExpedicao.'.pdf','D');
    }


    public function getLinhasByCarga ($produtos)
    {
        $cargas = array();
        $cargaAnterior = null;

        foreach ($produtos as $key => $produto) {
            if (($cargaAnterior != null) && ($cargaAnterior != $produto['carga'])) {
                $cargas[$produto['carga']][] = $produto['linhaSeparacao'];
            } else {
                if ($cargas == null) {
                    $cargas[$produto['carga']][] = $produto['linhaSeparacao'];
                } else {
                    if (!in_array($produto['linhaSeparacao'], $cargas[$produto['carga']])) {
                        $cargas[$produto['carga']][] = $produto['linhaSeparacao'];
                    }
                }
            }
            $cargaAnterior = $produto['carga'];
        }
        return $cargas;
    }

    public function getItinerariosByCarga ($produtos)
    {
        $cargas = array();
        $cargaAnterior = null;

        foreach ($produtos as $key => $produto) {
            if (($cargaAnterior != null) && ($cargaAnterior != $produto['carga'])) {
                $cargas[$produto['carga']][] = $produto['itinerario'];
            } else {
                if ($cargas == null) {
                    $cargas[$produto['carga']][] = $produto['itinerario'];
                } else {
                    if (!in_array($produto['itinerario'], $cargas[$produto['carga']])) {
                        $cargas[$produto['carga']][] = $produto['itinerario'];
                    }
                }
            }
            $cargaAnterior = $produto['carga'];
        }
        return $cargas;
    }

    public function cargas ($produtos)
    {
        $cargas = array();

        foreach ($produtos as $key => $produto) {
            if ($cargas == null) {
                $cargas[] = $produto['carga'];
            } else {
                if (!in_array($produto['carga'], $cargas)) {
                    $cargas[] = $produto['carga'];
                }
            }
        }

        $strCargas = "";
        foreach ($cargas as $carga) {
            if ($strCargas != "") {
                $strCargas = $strCargas . ", ";
            }
            $strCargas = $strCargas . $carga;
        }
        return $strCargas;
    }

    public function linhas ($produtos)
    {
        $linhas = array();
        foreach ($produtos as $key => $produto) {
            if ($linhas == null) {
                $linhas[] = $produto['linhaSeparacao'];
            } else {
                if (!in_array($produto['linhaSeparacao'], $linhas)) {
                    $linhas[] = $produto['linhaSeparacao'];
                }
            }
        }

        $strLinhas = "";
        foreach ($linhas as $linha) {
            if ($strLinhas != "") {
                $strLinhas = $strLinhas . ", ";
            }
            $strLinhas = $strLinhas . $linha;
        }
        return $strLinhas;
   }

    public function formataProduto($produtos, $embalado, $modelo)
    {
        $cargaAnterior = null;
        $qtdProduto = 0;
        $qtdCorteEmbalagem = 0;
        $qtdCorteVolume = 0;
        $linhaSeparacao = $this->getLinhasByCarga($produtos);
        $itinerarios    = $this->getItinerariosByCarga($produtos);
        unset($produto);
        foreach ($produtos as $key => $produto) {

            if ($cargaAnterior == null) {
                $this->drawHeaderCarga($linhaSeparacao, $produto['carga'],$itinerarios,$embalado, $modelo);
            }

            if (($cargaAnterior != null) && ($cargaAnterior != $produto['carga'])) {
                $this->ln();
                $this->ln();

                $qtdProduto  = 0;
                $qtdCorteEmbalagem = 0;
                $qtdCorteVolume = 0;

                $this->drawHeaderCarga($linhaSeparacao, $produto['carga'],$itinerarios,$embalado, $modelo);
            }

            $qtdProduto        = $qtdProduto + $produto['quantidade'];
            $qtdCorteEmbalagem = $qtdCorteEmbalagem + $produto['corteEmbalagem'];
            $qtdCorteVolume    = $qtdCorteVolume + $produto['corteVolume'];

            if (($produto == end($produtos)) ||
                !(
                    ($produtos[$key + 1]['bairro']     == $produto['bairro'])
                && ($produtos[$key + 1]['cliente']    == $produto['cliente'])
                && ($produtos[$key + 1]['codProduto'] == $produto['codProduto'])
                && ($produtos[$key + 1]['produto']    == $produto['produto'])
                && ($produtos[$key + 1]['grade']      == $produto['grade'])
                && ($produtos[$key + 1]['fabricante'] == $produto['fabricante'])
                )) {

                $qtdProduto        = $qtdProduto - $qtdCorteEmbalagem - $qtdCorteVolume;

                if ($qtdProduto > 0) {
                    $this->SetFont('Arial',"" , 6);
                    switch($modelo){
                        case 2:
                            $this->formataProdutoModelo2($produto,$embalado,$qtdProduto);
                            break;
                        default:
                            $this->formataProdutoModelo1($produto,$embalado,$qtdProduto);
                            break;
                    }
                }

                $qtdProduto  = 0;
                $qtdCorteEmbalagem = 0;
                $qtdCorteVolume = 0;
            }

            $cargaAnterior = $produto['carga'];
        }
    }

    /**
     * @param $linhaSeparacao
     * @param $produto
     */
    public function drawHeaderCarga($linhaSeparacao, $carga,$itinerarios, $embalado, $modelo)
    {
        $strLinha = "";
        $linhas = $linhaSeparacao[$carga];
        foreach ($linhas as $linha) {
            if ($strLinha != "") {
                $strLinha = $strLinha . ", ";
            }
            $strLinha = $strLinha . $linha;
        }
        $strIti = "";
        $listaItinerarios = $itinerarios[$carga];
        foreach ($listaItinerarios as $lItinerario) {
            if ($strIti != "") {
                $strIti = $strIti . ", ";
            }
            $strIti = $strIti . $lItinerario;
        }
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(285, 5, utf8_decode('Carga: ' . $carga . ' Linhas: ' . $strLinha), 1);
        $this->Ln();

        switch($modelo){
            case 2:
                $this->cabecalhoModelo2($strIti, $embalado);
                break;
            default:
                $this->cabecalhoModelo1($strIti, $embalado);
                break;
        }
    }

    private function cabecalhoModelo1($itinerario, $embalado){

        $this->Ln();
        $this->Cell(285, 5, utf8_decode("Itinerário: $itinerario"), 1);
        $this->Ln();

        if ($embalado == true) {
            $this->Cell(8, 5, "Seq.", 1);
            $this->Cell(102, 5, utf8_decode('Rua, Bairro, Cidade'), 1);
            $this->Cell(46, 5, utf8_decode('Cliente'), 1);
            $this->Cell(12, 5, utf8_decode('Vol.'), 1);
            $this->Cell(12, 5, utf8_decode('Código'), 1);
            $this->Cell(54, 5, utf8_decode('Produto'), 1);
            $this->Cell(18, 5, utf8_decode('Grade'), 1);
            $this->Cell(23, 5, utf8_decode('Fabricante'), 1);
            $this->Cell(10, 5, utf8_decode('Qtde.'), 1);
            $this->Ln();
        } else {
            $this->Cell(8, 5, "Seq.", 1);
            $this->Cell(106, 5, utf8_decode('Rua, Bairro, Cidade'), 1);
            $this->Cell(54, 5, utf8_decode('Cliente'), 1);
            $this->Cell(12, 5, utf8_decode('Código'), 1);
            $this->Cell(54, 5, utf8_decode('Produto'), 1);
            $this->Cell(18, 5, utf8_decode('Grade'), 1);
            $this->Cell(23, 5, utf8_decode('Fabricante'), 1);
            $this->Cell(10, 5, utf8_decode('Qtde.'), 1);
            $this->Ln();
        }
    }

    private function cabecalhoModelo2($itinerario, $embalado){

        $this->Ln();
        $this->Cell(285, 5, utf8_decode("Itinerário: $itinerario"), 1);
        $this->Ln();

        if ($embalado == true) {
            $this->Cell(8, 5, "Seq.", 1);
            $this->Cell(102, 5, utf8_decode('Rua, Bairro, Cidade'), 1);
            $this->Cell(46, 5, utf8_decode('Cliente'), 1);
            $this->Cell(12, 5, utf8_decode('Vol.'), 1);
            $this->Cell(12, 5, utf8_decode('Código'), 1);
            $this->Cell(72, 5, utf8_decode('Produto'), 1);
            $this->Cell(23, 5, substr(utf8_decode('Fabricante'),0,15), 1);
            $this->Cell(10, 5, utf8_decode('Qtde.'), 1);
            $this->Ln();
        } else {
            $this->Cell(8, 5, "Seq.", 1);
            $this->Cell(106, 5, utf8_decode('Rua, Bairro, Cidade'), 1);
            $this->Cell(54, 5, utf8_decode('Cliente'), 1);
            $this->Cell(12, 5, utf8_decode('Código'), 1);
            $this->Cell(72, 5, utf8_decode('Produto'), 1);
            $this->Cell(23, 5, substr(utf8_decode('Fabricante'),0,15), 1);
            $this->Cell(10, 5, utf8_decode('Qtde.'), 1);
            $this->Ln();
        }
    }

    public function formataProdutoModelo2($produto, $embalado, $qtdProduto){
        if ($embalado == true) {

            $codProduto = $produto['codProduto'];
            $grade = $produto['grade'];
            $pedido = $produto['pedido'];

            $etiquetaEn = $this->_em->getRepository("wms:Expedicao\EtiquetaSeparacao")->findOneBy(array('codProduto'=>$codProduto,'grade'=>$grade,'pedido'=>$pedido));
            $volume = "";
            if ($etiquetaEn != NULL) {
                $volumeEn = $etiquetaEn->getVolumePatrimonio();
                if ($volumeEn != NULL) $volume = $volumeEn->getId();
            }

            $this->Cell(8,  5, $produto['sequencia'], 1);
            $this->Cell(102, 5, utf8_decode($produto['rua'].', '.$produto['bairro'].', '.$produto['cidade'])     ,1);
            $this->Cell(46, 5, substr(utf8_decode($produto['cliente']),0,40)    ,1);
            $this->Cell(12, 5, utf8_decode($volume)     ,1);
            $this->Cell(12, 5, utf8_decode($produto['codProduto']) ,1);
            $this->Cell(72, 5, utf8_decode($produto['produto'])    ,1);
            $this->Cell(23, 5, substr(utf8_decode($produto['fabricante']),0,15) ,1);
            $this->Cell(10, 5, utf8_decode($qtdProduto) ,1,1);
        } else {
            $this->Cell(8,  5, $produto['sequencia'], 1);
            $this->Cell(106, 5, utf8_decode($produto['rua'].', '.$produto['bairro'].', '.$produto['cidade'])     ,1);
            $this->Cell(54, 5, substr(utf8_decode($produto['cliente']),0,40)    ,1);
            $this->Cell(12, 5, utf8_decode($produto['codProduto']) ,1);
            $this->Cell(72, 5, utf8_decode($produto['produto'])    ,1);
            $this->Cell(23, 5, substr(utf8_decode($produto['fabricante']),0,15) ,1);
            $this->Cell(10, 5, utf8_decode($qtdProduto) ,1,1);
        }


    }

    public function formataProdutoModelo1($produto, $embalado, $qtdProduto){
        if ($embalado == true) {

            $codProduto = $produto['codProduto'];
            $grade = $produto['grade'];
            $pedido = $produto['pedido'];

            $etiquetaEn = $this->_em->getRepository("wms:Expedicao\EtiquetaSeparacao")->findOneBy(array('codProduto'=>$codProduto,'grade'=>$grade,'pedido'=>$pedido));
            $volume = "";
            if ($etiquetaEn != NULL) {
                $volumeEn = $etiquetaEn->getVolumePatrimonio();
                if ($volumeEn != NULL) $volume = $volumeEn->getId();
            }

            $this->Cell(8,  5, $produto['sequencia'], 1);
            $this->Cell(102, 5, utf8_decode($produto['rua'].', '.$produto['bairro'].', '.$produto['cidade'])     ,1);
            $this->Cell(46, 5, substr(utf8_decode($produto['cliente']),0,40)    ,1);
            $this->Cell(12, 5, utf8_decode($volume)     ,1);
            $this->Cell(12, 5, utf8_decode($produto['codProduto']) ,1);
            $this->Cell(54, 5, utf8_decode($produto['produto'])    ,1);
            $this->Cell(18, 5, utf8_decode($produto['grade'])      ,1);
            $this->Cell(23, 5, utf8_decode($produto['fabricante']) ,1);
            $this->Cell(10, 5, utf8_decode($qtdProduto) ,1,1);
        } else {
            $this->Cell(8,  5, $produto['sequencia'], 1);
            $this->Cell(106, 5, utf8_decode($produto['rua'].', '.$produto['bairro'].', '.$produto['cidade'])     ,1);
            $this->Cell(54, 5, substr(utf8_decode($produto['cliente']),0,40)    ,1);
            $this->Cell(12, 5, utf8_decode($produto['codProduto']) ,1);
            $this->Cell(54, 5, utf8_decode($produto['produto'])    ,1);
            $this->Cell(18, 5, utf8_decode($produto['grade'])      ,1);
            $this->Cell(23, 5, utf8_decode($produto['fabricante']) ,1);
            $this->Cell(10, 5, utf8_decode($qtdProduto) ,1,1);
        }
    }

}
