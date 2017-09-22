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
                foreach ($produto as $dscGrade => $grade) {
                    foreach ($grade as $quebraPD => $criterios) {
                        foreach ($criterios as $codCriterio => $separacoes) {
                            foreach ($separacoes['tiposSaida'] as $tipoSaida => $enderecos) {
                                foreach ($enderecos['enderecos'] as $idEndereco => $pedidos) {
                                    foreach ($pedidos as $codPedido =>$itens){
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

    public function gerarReservaSaidaPicking ($produtos, $repositorios){
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        foreach ($produtos as $produto){
            $reservaEstoqueRepo->adicionaReservaEstoque($produto['idPicking'],$produto['produtos'],"S","E",$produto['idExpedicao'],null,null,null, $repositorios);
        }
    }


    public function gerarReservaSaidaPulmao ($produtos, $repositorios)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $repositorios['estoqueRepo'];
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];

        $arrItensReserva = array();
        $arrEstoqueReservado = array();

        foreach ($produtos as $produto) {
            $idExpedicao = $produto['idExpedicao'];
            $idPedido = $produto['idPedido'];
            $codProduto = $produto['produtos'][0]['codProduto'];
            $grade = $produto['produtos'][0]['grade'];
            $qtdRestante = $produto['produtos'][0]['qtd'];
            $idVolume = $produtos['produtos'][0]['codProdutoVolume'];
            $qtdRestante = $qtdRestante * -1;
            $params = array(
                'idProduto'=>$codProduto,
                'grade'=> $grade,
                'idVolume'=>$idVolume,
                'idCaracteristigaIgnorar' => Endereco::ENDERECO_PICKING
            );

            $estoquePulmao = $estoqueRepo->getEstoqueByParams($params);
            foreach ($estoquePulmao as $estoque) {
                if ($qtdRestante > 0) {
                    $qtdEstoque = $estoque['SALDO'];
                    $idPulmao = $estoque['COD_DEPOSITO_ENDERECO'];
                    $zerouEstoque = false;
                    $nextEndereco = false;

                    if(isset($arrEstoqueReservado[$idPulmao][$codProduto][$grade])) {
                        $reserva = $arrEstoqueReservado[$idPulmao][$codProduto][$grade];
                        if ($reserva['estoqueReservado']){
                            $nextEndereco = true;
                        } else {
                            $qtdEstoque -= $reserva['qtdReservada'];
                        }
                    }

                    if ($nextEndereco)
                        continue;

                    if ($qtdRestante >= $qtdEstoque) {
                        $qtdSeparar = $qtdEstoque;
                        $zerouEstoque = true;
                    } else {
                        $qtdSeparar = $qtdRestante;
                    }

                    $qtdRestante = $qtdRestante - $qtdSeparar;
                    $produtosSeparar = $produto['produtos'];
                    foreach ($produtosSeparar as $key=> $produtoSeparar){
                        $produtosSeparar[$key]['qtd'] = ($qtdSeparar * -1);
                    }

                    $arrItensReserva[$idExpedicao][$idPedido][$codProduto][$grade][$idPulmao]['itens'] = $produtosSeparar;
                    if(isset($arrEstoqueReservado[$idPulmao][$codProduto][$grade]['qtdReservada'])) {
                        $arrEstoqueReservado[$idPulmao][$codProduto][$grade]['qtdReservada'] += $qtdSeparar;
                    } else {
                        $arrEstoqueReservado[$idPulmao][$codProduto][$grade]['qtdReservada'] = $qtdSeparar;
                    }
                    $arrEstoqueReservado[$idPulmao][$codProduto][$grade]['estoqueReservado'] = $zerouEstoque;
                }
            }
        }

        foreach ($arrItensReserva as $idExpedicao => $expedicao){
            foreach ($expedicao as $idPedido => $pedido) {
                foreach ($pedido as $produto) {
                    foreach ($produto as $grade) {
                        foreach ($grade as $idPulmao => $endPulmao){
                            $itens = $endPulmao['itens'];
                            $reservaEstoqueRepo->adicionaReservaEstoque($idPulmao,$itens,"S","E",$idExpedicao,null,null,null, $repositorios);
                        }
                    }
                }
            }
        }

        $this->getEntityManager()->flush();
    }
}
