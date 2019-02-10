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

        if (!empty($produtosImpedidos)) {
            $pdf = new \Wms\Module\Web\Report\Generico("L");
            $pdf->init($produtosImpedidos, "Impedimentos por inventário", "Produtos/Endereços impedidos por inventário(s) em aberto");
            throw new \Exception("Existem produtos ou endereços à serem reservados que estão em processo de inventário");
        }
    }
}