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
        $this->Cell(45, 10, utf8_decode("RELATÓRIO CARREGAMENTO POR PRODUTO"),0,1);
    }

    private function bodyPage($data, $dataEmb = null){

        if (isset($dataEmb) && !empty($dataEmb)) {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 6, utf8_decode($dataEmb['SEQUENCIA']),0,0);
            $this->Cell(30, 6, utf8_decode($dataEmb['QUANTIDADE_CONFERIDA']),0,0);
            $this->Cell(40, 6, utf8_decode(substr($dataEmb['COD_MAPA_SEPARACAO_EMB_CLIENTE'],0,27)),0,0);
            $this->Cell(70, 6, $dataEmb['NOM_PESSOA'],0,1);
        } else {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(10, 6, utf8_decode($data['SEQUENCIA']),0,0);
            $this->Cell(20, 6, utf8_decode($data['COD_PRODUTO']),0,0,'R');
            $this->Cell(100, 6, utf8_decode($data['DSC_PRODUTO']),0,0);
            $this->Cell(20, 6, utf8_decode(floor(number_format($data['QUANTIDADE_CONFERIDA'],3,'.','')) . ' ' . $data['DESCRICAO_EMBALAGEM']),0,1);
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
        $produtoRepo = $em->getRepository('wms:Produto');

        $linhaSeparacaoAnt = null;
        $sequenciaAnt      = null;
        $codProdutoAnt     = null;
        $gradeAnt          = null;

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
                $this->Cell(45, 10, utf8_decode("Placa: $valor[DSC_PLACA_CARGA]"),0,0);
                $this->Cell(45, 10, utf8_decode("Peso: $pesoTotal kg"),0,0);
                $this->Cell(20, 10, utf8_decode("Cubagem: $cubagemTotal m³"),0,1);

                $this->Line(10,60,200,60);

                $this->SetFont('Arial',  "B", 8);
                $this->Cell(10, 15, utf8_decode("Seq.:"),0,0);
                $this->Cell(20, 15, utf8_decode("Cód. Prod.:"),0,0);
                $this->Cell(100, 15, utf8_decode("Produto:"),0,0);
                $this->Cell(20, 15, utf8_decode("Caixa Master:"),0,0);
                $this->Cell(20, 15, utf8_decode("Unidade:"),0,0);
                $this->Cell(20, 15, utf8_decode("Total:"),0,1);
            }

            if ($codProdutoAnt != $valor['COD_PRODUTO'] || $gradeAnt != $valor['DSC_GRADE']) {
                $this->bodyPage($valor);
            }

            $linhaSeparacaoAnt = $valor['DSC_LINHA_SEPARACAO'];
            $codProdutoAnt     = $valor['COD_PRODUTO'];
            $sequenciaAnt      = $valor['SEQUENCIA'];
            $gradeAnt          = $valor['DSC_GRADE'];
        }

        $sequencia = 99999;
        foreach ($embalados as $embalado) {
            if ($sequencia != $embalado['SEQUENCIA']) {
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

                $sequencia = $embalado['SEQUENCIA'];
            }


            $this->bodyPage(null, $embalado);
        }

        $this->Output('consultaCarregamento.pdf','D');

    }

}
