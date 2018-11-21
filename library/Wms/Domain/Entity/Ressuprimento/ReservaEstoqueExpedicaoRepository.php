<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Module\Web\Form\Deposito\Endereco\Caracteristica;

class ReservaEstoqueExpedicaoRepository extends EntityRepository
{

    public function gerarReservaSaida ($expedicoes, $repositorios)
    {
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        foreach ($expedicoes as $idExpedicao => $produtos) {
            foreach ($produtos as $codProduto => $produto) {
                foreach ($produto as $dscGrade => $lotes) {
                    foreach ($lotes as $lote => $grade) {
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
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
