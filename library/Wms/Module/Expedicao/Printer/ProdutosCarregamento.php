<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Domain\Entity\Expedicao;

class ProdutosCarregamento extends Pdf
{
    private function startPage() {

        $this->AddPage();

        $this->SetFont('Arial','',7.5);
        $this->Cell(85, 10, utf8_decode(date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(85, 10, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');

        $this->SetFont('Arial','B',14);
        $this->Cell(45, 10, utf8_decode("CONSULTAR CARREGAMENTO POR EXPEDICAO"),0,1);
    }

    private function bodyPage($data, $embalagemRepo, $dataEmb = null){

        if (isset($dataEmb) && !empty($dataEmb)) {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 15, utf8_decode($dataEmb['SEQUENCIA']),0,0);
            $this->Cell(30, 15, utf8_decode($dataEmb['QUANTIDADE_CONFERIDA']),0,0);
            $this->Cell(40, 15, utf8_decode(substr($dataEmb['COD_MAPA_SEPARACAO_EMB_CLIENTE'],0,27)),0,0);
            $this->Cell(70, 15, $dataEmb['NOM_PESSOA'],0,1);
        } else {
            $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $data['COD_PRODUTO'], 'grade' => $data['DSC_GRADE']), array('quantidade' => 'DESC'));

            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 15, utf8_decode($data['SEQUENCIA']),0,0);
            $this->Cell(20, 15, utf8_decode($data['COD_PRODUTO']),0,0);
            $this->Cell(70, 15, utf8_decode(substr($data['DSC_PRODUTO'],0,27)),0,0);
            $qtdTotal = $data['QUANTIDADE_CONFERIDA'];
            foreach ($embalagemEntities as $embalagemEntity) {
                $this->Cell(30, 15, utf8_decode(floor($data['QUANTIDADE_CONFERIDA'] / $embalagemEntity->getQuantidade()) . ' ' . $embalagemEntity->getDescricao()),0,0);
                $data['QUANTIDADE_CONFERIDA'] = $data['QUANTIDADE_CONFERIDA'] % $embalagemEntity->getQuantidade();
            }
            if (count($embalagemEntities) < 2) {
                $this->Cell(30, 15, '',0,0);
            }

            $this->Cell(30, 15, $qtdTotal.' und.',0,1);
        }

    }

    public function imprimir($idExpedicao)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $resultado = $mapaSeparacaoConferenciaRepo->getConferidosByExpedicao($idExpedicao);
        $embalados = $mapaSeparacaoConferenciaRepo->getEmbaladosConferidosByExpedicao($idExpedicao);
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
        $produtoRepo = $em->getRepository('wms:Produto');

        $linhaSeparacaoAnt = null;
        $sequenciaAnt      = null;

        $pesoTotal = 0;
        $cubagemTotal = 0;
        foreach ($resultado as $valorPesoCubagem) {
            $produtoEntity = $produtoRepo->getPesoProduto($valorPesoCubagem);
            if (isset($produtoEntity) && !empty($produtoEntity)) {
                $pesoTotal = $pesoTotal + $produtoEntity[0]['NUM_PESO'];
                $cubagemTotal = $cubagemTotal + $produtoEntity[0]['NUM_CUBAGEM'];
            }
        }

        foreach ($resultado as $chave => $valor) {

            if ($valor['DSC_LINHA_SEPARACAO'] != $linhaSeparacaoAnt || $valor['SEQUENCIA'] != $sequenciaAnt) {
                $this->startPage();
                $dataExpedicao = new \DateTime($valor['DTH_INICIO']);
                $dataExpedicao = $dataExpedicao->format('d/m/Y');
                $this->SetFont('Arial',  "B", 12);
                $this->Line(10,20,200,20);
                $this->Cell(45, 10, utf8_decode("Expedição: $idExpedicao"),0,0);
                $this->Cell(45, 10, utf8_decode("Data: $dataExpedicao"),0,0);
                $this->Cell(20, 10, utf8_decode("Linha de Separação: $valor[DSC_LINHA_SEPARACAO]"),0,1);
                $this->Cell(45, 10, utf8_decode("Peso: $pesoTotal kg"),0,0);
                $this->Cell(20, 10, utf8_decode("Cubagem: $cubagemTotal m³"),0,1);

                $this->Line(10,60,200,60);

                $this->SetFont('Arial',  "B", 8);
                $this->Cell(20, 15, utf8_decode("Sequência:"),0,0);
                $this->Cell(20, 15, utf8_decode("Cód. Prod.:"),0,0);
                $this->Cell(70, 15, utf8_decode("Produto:"),0,0);
                $this->Cell(30, 15, utf8_decode("Caixa Master:"),0,0);
                $this->Cell(30, 15, utf8_decode("Unidade:"),0,0);
                $this->Cell(30, 15, utf8_decode("Total:"),0,1);
            }

            $this->bodyPage($valor, $embalagemRepo);

            $linhaSeparacaoAnt = $valor['DSC_LINHA_SEPARACAO'];
            $sequenciaAnt      = $valor['SEQUENCIA'];

        }

        foreach ($embalados as $embalado) {
            $this->startPage();
            $dataExpedicao = new \DateTime($embalado['DTH_INICIO']);
            $dataExpedicao = $dataExpedicao->format('d/m/Y');
            $this->SetFont('Arial',  "B", 12);
            $this->Line(10,20,200,20);
            $this->Cell(45, 10, utf8_decode("Expedição: $idExpedicao"),0,0);
            $this->Cell(45, 10, utf8_decode("Data: $dataExpedicao"),0,1);
            $this->Cell(20, 10, utf8_decode("Linha de Separação: $embalado[DSC_QUEBRA]"),0,1);
            $this->Cell(45, 10, utf8_decode("Peso: $pesoTotal kg"),0,0);
            $this->Cell(20, 10, utf8_decode("Cubagem: $cubagemTotal m³"),0,1);

            $this->Line(10,70,200,70);

            $this->SetFont('Arial',  "B", 8);
            $this->Cell(20, 15, utf8_decode("Sequência:"),0,0);
            $this->Cell(30, 15, utf8_decode("Qtd. Conferir:"),0,0);
            $this->Cell(40, 15, utf8_decode("Cod. Embalado:"),0,0);
            $this->Cell(70, 15, utf8_decode("Cliente:"),0,1);

            $this->bodyPage(null, null, $embalado);
        }

        $this->Output('consultaCarregamento.pdf','D');

    }

}
