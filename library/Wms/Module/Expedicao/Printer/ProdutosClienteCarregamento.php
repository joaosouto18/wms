<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Domain\Entity\Expedicao;
use Wms\Math;

class ProdutosClienteCarregamento extends Pdf
{
    protected $math;

    private function startPage($produtosClientesInConferenciaTxt) {

        $this->AddPage();

        $this->SetFont('Arial','',7.5);
        $this->Cell(85, 10, utf8_decode(date('d/m/Y')." às ".date('H:i:s')), 0, 0, "L");
        $this->Cell(85, 10, utf8_decode('Página ').$this->PageNo(), 0, 1, 'R');

        $this->SetFont('Arial','B',14);
        $this->Cell(45, 10, utf8_decode("RELATÓRIO CARREGAMENTO POR CLIENTE - $produtosClientesInConferenciaTxt"),0,1);
    }

    private function bodyPage($data, $embalagemRepo, $dataEmb = null){

        if (isset($dataEmb) && !empty($dataEmb)) {
            $this->SetFont('Arial',  '', 10);
            $this->Cell(20, 6, utf8_decode($dataEmb['SEQUENCIA']),0,0);
            $this->Cell(30, 6, utf8_decode($dataEmb['QUANTIDADE_CONFERIDA']),0,0);
            $this->Cell(40, 6, utf8_decode(substr($dataEmb['COD_MAPA_SEPARACAO_EMB_CLIENTE'],0,27)),0,0);
            $this->Cell(70, 6, utf8_decode($dataEmb['NOM_PESSOA']),0,1);
        } else {
            $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $data['COD_PRODUTO'], 'grade' => $data['DSC_GRADE'], 'dataInativacao' => null), array('quantidade' => 'DESC'));


            $this->SetFont('Arial',  '', 10);
            $this->Cell(10, 6, utf8_decode($data['SEQUENCIA']),0,0);
            $this->Cell(20, 6, utf8_decode($data['COD_PRODUTO']),0,0);
            $this->Cell(110, 6, utf8_decode($data['DSC_PRODUTO']),0,0);
            $qtdTotal = $data['QUANTIDADE_CONFERIDA'];

            foreach ($embalagemEntities as $embalagemEntity) {

                if(Math::resto($data['QUANTIDADE_CONFERIDA'],$embalagemEntity->getQuantidade()) == 0) {
                    $this->Cell(20, 6, $data['QUANTIDADE_CONFERIDA'] / $embalagemEntity->getQuantidade() . ' ' . $embalagemEntity->getDescricao());
                    break;
                }
            }

            $this->Cell(20, 6, $qtdTotal.' und.',0,1,'R');
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
        $resultado = $mapaSeparacaoConferenciaRepo->getProdutosClientesByExpedicao($idExpedicao,$idLinhaSeparacao);

        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
        $produtoRepo = $em->getRepository('wms:Produto');

        $produtosClientesInConferenciaTxt = "Conferência em andamento";
        $produtosClientesInConferencia = $mapaSeparacaoConferenciaRepo->getProdutosClientesInConferencia($idExpedicao);
        if(is_array($produtosClientesInConferencia) && count($produtosClientesInConferencia)===0) {
            $produtosClientesInConferenciaTxt = "Conferência realizada";
        }

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

        $pedidoAnterior = null;
        $clienteAnterior = null;
        $sequenciaAnterior = null;
        foreach ($resultado as $chave => $valor) {
            if ($valor['COD_PESSOA'] != $clienteAnterior || $valor['SEQUENCIA'] != $sequenciaAnterior) {
                $this->startPage($produtosClientesInConferenciaTxt);
                $dataExpedicao = new \DateTime($valor['DTH_INICIO']);
                $dataExpedicao = $dataExpedicao->format('d/m/Y');
                $this->SetFont('Arial', "B", 12);
                $this->Line(10,20,200,20);
                $this->Cell(25, 6, utf8_decode('Expedição: '),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(45, 6, utf8_decode($idExpedicao.' - '.$valor['COD_PEDIDO']),0,0);
                $this->SetFont('Arial', "B", 12);
                $this->Cell(12, 6, utf8_decode('Data: '),0,0);
                $this->SetFont('Arial',  '', 12);
                $this->Cell(35, 6, utf8_decode($dataExpedicao),0,0);
                $this->SetFont('Arial', "B", 12);
                $this->Cell(15, 6, utf8_decode('Placa: '),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(45, 6, utf8_decode($valor['DSC_PLACA_CARGA']),0,1);
                $this->SetFont('Arial', "B", 12);
                $this->Cell(25, 6, utf8_decode("Cliente: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(92, 6, utf8_decode(substr($valor['NOM_PESSOA'],0,35)),0,0);
                $this->SetFont('Arial', "B", 12);
                $this->Cell(27, 6, utf8_decode("CPF - CNPJ: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(20, 6, utf8_decode($valor['CPF_CNPJ']),0,1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(25, 6, utf8_decode("Endereço: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(92, 6, utf8_decode(substr($valor['DSC_ENDERECO'],0,35)),0,0);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(15, 6, utf8_decode("Bairro: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(40, 6, utf8_decode($valor['NOM_BAIRRO']),0,1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(25, 6, utf8_decode("Cidade: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(45, 6, utf8_decode($valor['NOM_LOCALIDADE']),0,0);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(8, 6, utf8_decode("UF: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(40, 6, utf8_decode($valor['COD_REFERENCIA_SIGLA']),0,1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(25, 6, utf8_decode("Peso: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(45, 6, utf8_decode("$pesoTotal kg"),0,0);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(23, 6, utf8_decode("Cubagem: "),0,0);
                $this->SetFont('Arial', '', 12);
                $this->Cell(20, 6, utf8_decode("$cubagemTotal m³"),0,1);

                $this->Line(10,70,200,70);

                $this->SetFont('Arial',  "B", 8);
                $this->Cell(10, 15, utf8_decode("Seq.:"),0,0);
                $this->Cell(20, 15, utf8_decode("Cód. Prod.:"),0,0);
                $this->Cell(110, 15, utf8_decode("Produto:"),0,0);
                $this->Cell(20, 15, utf8_decode("Unidade:"),0,0);
                $this->Cell(10, 15, utf8_decode("Total:"),0,1);
            }

            if (isset($valor['COD_MAPA_SEPARACAO_EMB_CLIENTE']) && !empty($valor['COD_MAPA_SEPARACAO_EMB_CLIENTE'])) {
                $this->bodyPage(null, null, $valor);
            } else {
                $this->bodyPage($valor, $embalagemRepo);
            }
            $clienteAnterior = $valor['COD_PESSOA'];
            $sequenciaAnterior = $valor['SEQUENCIA'];
        }
        $this->Output('consultaCarregamento.pdf','D');
    }

}
