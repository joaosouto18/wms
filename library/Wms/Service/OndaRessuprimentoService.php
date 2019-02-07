<?php
/**
 * Created by PhpStorm.
 * User: tarci
 * Date: 07/02/2019
 * Time: 15:18
 */

namespace Wms\Service;


class OndaRessuprimentoService extends AbstractService
{
    public function checkImpedimentoReservas($reservas)
    {
        $prodsEnds = [];
        foreach ($reservas as $idExpedicao => $produtos) {
            foreach ($produtos as $codProduto => $produto) {
                foreach ($produto as $dscGrade => $lotes) {
                    foreach ($lotes as $lote => $grade) {
                        foreach ($grade as $quebraPD => $criterios) {
                            foreach ($criterios as $codCriterio => $separacoes) {
                                foreach ($separacoes['tiposSaida'] as $tipoSaida => $enderecos) {
                                    foreach ($enderecos['enderecos'] as $idEndereco => $pedidos) {
                                        $prodsEnds[$idEndereco][] = [
                                            "codigo" => $codProduto,
                                            "grade" => $dscGrade
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $produtosImpedidos = $this->em->getRepository("wms:InventarioNovo")->checkProdutosPedidos($prodsEnds);
    }
}