<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Expedicao\PedidoProdutoLoteRepository;
use Wms\Domain\Entity\Produto\Lote;

class ReservaEstoqueExpedicaoRepository extends EntityRepository
{

    public function gerarReservaSaida ($expedicoes, $repositorios)
    {
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];

        /** @var PedidoProdutoLoteRepository $pedProdLoteRepo */
        $pedProdLoteRepo = $this->_em->getRepository("wms:Expedicao\PedidoProdutoLote");

        $arrLoteDefWMS = [];

        foreach ($expedicoes as $idExpedicao => $produtos) {
            foreach ($produtos as $codProduto => $produto) {
                foreach ($produto as $dscGrade => $lotes) {
                    foreach ($lotes as $lote => $grade) {
                        $prefixoLote = explode("*#*", $lote)[0];
                        foreach ($grade as $quebraPD => $criterios) {
                            foreach ($criterios as $codCriterio => $separacoes) {
                                foreach ($separacoes['tiposSaida'] as $tipoSaida => $enderecos) {
                                    foreach ($enderecos['enderecos'] as $idEndereco => $pedidos) {
                                        foreach ($pedidos as $codPedido => $normas) {
                                            foreach ($normas as $codNorma => $itens) {
                                                $criterioReserva = array(
                                                    'expedicao' => $idExpedicao,
                                                    'pedido' => $codPedido,
                                                    'tipoSaida' => $tipoSaida,
                                                    'quebraPulmaoDoca' => $quebraPD,
                                                    'codCriterioPD' => $codCriterio
                                                );
                                                $reservaEstoqueRepo->adicionaReservaEstoque($idEndereco, $itens, "S", "E", $criterioReserva, null, null, null, $repositorios);
                                            }
                                            if ($prefixoLote === Lote::LND && $tipoSaida != ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE) {
                                                $unikIndex = "$codPedido*#*$codProduto*#*$dscGrade";
                                                if (!isset($arrLoteDefWMS[$unikIndex]))
                                                    $arrLoteDefWMS[$unikIndex] = [
                                                        'codPedido' => $codPedido,
                                                        'codProduto' => $codProduto,
                                                        'grade' => $dscGrade,
                                                    ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($arrLoteDefWMS)) {
                $this->_em->flush();
                $pedProdLoteRepo->generatePedidoProdutoLoteByReserva($arrLoteDefWMS, $this);
            }
        }
    }

    /**
     * @param $itemPedido
     * @return array
     */
    public function getSaldoReservadoByItemPedido($itemPedido)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("p.id codPedido, rep.lote, rep.codProduto, rep.grade, SUM(rep.qtd) qtd")
            ->from("wms:Ressuprimento\ReservaEstoqueProduto", "rep")
            ->innerJoin("wms:Ressuprimento\ReservaEstoqueExpedicao", "ree", "WITH", "ree.reservaEstoque = rep.reservaEstoque")
            ->innerJoin("ree.reservaEstoque", "re")
            ->innerJoin("ree.pedido", "p")
            ->innerJoin("wms:Expedicao\PedidoProduto", "pp", "WITH", "pp.pedido = ree.pedido AND pp.codProduto = rep.codProduto AND pp.grade = rep.grade")
            ->where("re.atendida = 'N' AND rep.lote IS NOT NULL AND rep.codProduto = :codProduto AND rep.grade = :grade AND ree.pedido = :pedido")
            ->andWhere("NOT EXISTS (SELECT 'x' FROM wms:Expedicao\PedidoProdutoLote ppl WHERE ppl.pedidoProduto = pp)")
            ->setParameters([":codProduto" => $itemPedido['codProduto'], ":grade" => $itemPedido['grade'], ":pedido" => $itemPedido['codPedido']])
            ->groupBy("p.id, rep.lote, rep.codProduto, rep.grade")
        ;

        return $dql->getQuery()->getResult();
    }
}
