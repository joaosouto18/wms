<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Domain\Entity\Expedicao;
use Wms\Math;

class ProdutosCarregamento extends Pdf
{
    private function startPage($produtosClientesInConferenciaTxt) {

        $this->AddPage();

        $this->SetFont('Arial','',7.5);
        $this->Cell(85, 10, utf8_decode(date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(85, 10, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');

        $this->SetFont('Arial','B',14);
        $this->Cell(45, 10, utf8_decode("RELATÓRIO CARREGAMENTO POR PRODUTO - $produtosClientesInConferenciaTxt"),0,1);
    }

    private function bodyPage($data, $dataEmb = null, $embalagemRepo = null){

        if (isset($dataEmb) && !empty($dataEmb)) {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 6, utf8_decode($dataEmb['SEQUENCIA']),0,0);
            $this->Cell(30, 6, utf8_decode($dataEmb['QUANTIDADE_CONFERIDA']),0,0);
            $this->Cell(40, 6, utf8_decode(substr($dataEmb['COD_MAPA_SEPARACAO_EMB_CLIENTE'],0,27)),0,0);
            $this->Cell(70, 6, $dataEmb['NOM_PESSOA'],0,1);
        } else {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 6, utf8_decode($data['COD_EXTERNO']),0,0);
            $this->Cell(20, 6, utf8_decode($data['COD_PRODUTO']),0,0,'R');
            $this->Cell(110, 6, utf8_decode(substr($data['DSC_PRODUTO'],0,50)),0,0);

            $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $data['COD_PRODUTO'], 'grade' => $data['DSC_GRADE'], 'dataInativacao' => null), array('quantidade' => 'DESC'));
            $qtdTotal = $data['QUANTIDADE_CONFERIDA'];
            foreach ($embalagemEntities as $embalagemEntity) {
                if (Math::resto($data['QUANTIDADE_CONFERIDA'],$embalagemEntity->getQuantidade()) == 0) {
                    $this->Cell(20, 6, $data['QUANTIDADE_CONFERIDA'] / $embalagemEntity->getQuantidade() . ' ' . $embalagemEntity->getDescricao());
                    break;
                }
            }
            $this->Cell(10, 6, $qtdTotal.' und.',0,1,'R');
        }
    }

    public function imprimir($idExpedicao,$idLinhaSeparacao)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $em->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $resultado = $mapaSeparacaoConferenciaRepo->getConferidosByExpedicao($idExpedicao,$idLinhaSeparacao);
        $embalados = $mapaSeparacaoConferenciaRepo->getEmbaladosConferidosByExpedicao($idExpedicao,$idLinhaSeparacao);
        $produtoRepo = $em->getRepository('wms:Produto');
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');

        $produtosClientesInConferenciaTxt = "Conferência em andamento";
        $produtosClientesInConferencia = $mapaSeparacaoConferenciaRepo->getProdutosClientesInConferencia($idExpedicao);
        if(is_array($produtosClientesInConferencia) && count($produtosClientesInConferencia)===0) {
            $produtosClientesInConferenciaTxt = "Conferência realizada";
        }

        $linhaSeparacaoAnt = null;
        $sequenciaAnt      = null;
        $pedidoAnt         = null;

        $pesoTotal = 0;
        $cubagemTotal = 0;
        $volumeTotal = 0;
        foreach ($resultado as $valorPesoCubagem) {
            $produtoEntity = $produtoRepo->getPesoProduto($valorPesoCubagem);
            if (isset($produtoEntity) && !empty($produtoEntity)) {
                $pesoTotal = $pesoTotal + $produtoEntity[0]['NUM_PESO'];
                $cubagemTotal = $cubagemTotal + $produtoEntity[0]['NUM_CUBAGEM'];
            }

            $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $valorPesoCubagem['COD_PRODUTO'], 'grade' => $valorPesoCubagem['DSC_GRADE'], 'dataInativacao' => null), array('quantidade' => 'DESC'));
            $qtdTotal = $valorPesoCubagem['QUANTIDADE_CONFERIDA'];
            if ($valorPesoCubagem['COD_EXTERNO'] == $pedidoAnt) {
                if (!isset($volumesPedido[$valorPesoCubagem['COD_EXTERNO']]))
                    $volumesPedido[$valorPesoCubagem['COD_EXTERNO']] = 0;

                foreach ($embalagemEntities as $embalagemEntity) {
                    if (Math::resto($valorPesoCubagem['QUANTIDADE_CONFERIDA'],$embalagemEntity->getQuantidade()) == 0) {
                        $volumesPedido[$valorPesoCubagem['COD_EXTERNO']] = $volumesPedido[$valorPesoCubagem['COD_EXTERNO']] + ($valorPesoCubagem['QUANTIDADE_CONFERIDA'] / $embalagemEntity->getQuantidade());
                        break;
                    }
                }

            }
            foreach ($embalagemEntities as $embalagemEntity) {
                if (Math::resto($valorPesoCubagem['QUANTIDADE_CONFERIDA'],$embalagemEntity->getQuantidade()) == 0) {
                    $volumeTotal = $volumeTotal + ($valorPesoCubagem['QUANTIDADE_CONFERIDA'] / $embalagemEntity->getQuantidade());
                    break;
                }
            }
            $pedidoAnt = $valorPesoCubagem['COD_EXTERNO'];
        }

        $pedidoAnterior = null;
        foreach ($resultado as $chave => $valor) {

            if ($valor['DSC_LINHA_SEPARACAO'] != $linhaSeparacaoAnt || $valor['SEQUENCIA'] != $sequenciaAnt) {
                $this->startPage($produtosClientesInConferenciaTxt);
                $dataExpedicao = new \DateTime($valor['DTH_INICIO']);
                $dataExpedicao = $dataExpedicao->format('d/m/Y');
                $this->SetFont('Arial',  "B", 12);
                $this->Line(10,20,200,20);
                $this->Cell(45, 10, utf8_decode("Expedição: $idExpedicao"),0,0);
                $this->Cell(45, 10, utf8_decode("Data: $dataExpedicao"),0,0);
                $this->Cell(20, 10, utf8_decode("Linha de Separação: $valor[DSC_LINHA_SEPARACAO]"),0,1);
                $this->Cell(45, 10, utf8_decode("Placa: $valor[DSC_PLACA_CARGA]"),0,0);
                $this->Cell(45, 10, utf8_decode("Peso: $pesoTotal kg"),0,0);
                $this->Cell(45, 10, utf8_decode("Cubagem: $cubagemTotal m³"),0,0);
                $this->Cell(20, 10, utf8_decode("Volumes: $volumeTotal"),0,1);
                $this->Cell(20, 10, utf8_decode("Motorista: ________________________________________________"),0,1);
                $this->Cell(20, 10, utf8_decode("Transportadora:___________________________________________"),0,1);

                $this->Line(10,70,200,70);

                $this->SetFont('Arial',  "B", 8);
                $this->Cell(20, 15, utf8_decode("Seq.:"),0,0);
                $this->Cell(20, 15, utf8_decode("Cód. Prod.:"),0,0);
                $this->Cell(110, 15, utf8_decode("Produto:"),0,0);
                $this->Cell(20, 15, utf8_decode("Unidade:"),0,0);
                $this->Cell(10, 15, utf8_decode("Total:"),0,1);
            }

            $this->bodyPage($valor,null,$embalagemRepo);

            if (isset($volumesPedido[$pedidoAnterior])) {
                if ($valor['COD_EXTERNO'] != $pedidoAnterior) {
                    $this->Cell(100, 6, $volumesPedido[$pedidoAnterior].' VOLUMES.',0,1,'R');
                }
            }

            $linhaSeparacaoAnt = $valor['DSC_LINHA_SEPARACAO'];
            $sequenciaAnt      = $valor['SEQUENCIA'];
            $pedidoAnterior    = $valor['COD_EXTERNO'];
        }

        $sequencia = 99999;
        foreach ($embalados as $embalado) {
            if ($sequencia != $embalado['SEQUENCIA']) {
                $this->startPage($produtosClientesInConferenciaTxt);
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
