<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Module\Web\Form\Deposito\Endereco\Caracteristica;

class ReservaEstoqueExpedicaoRepository extends EntityRepository
{

    public function gerarReservaSaidaPicking ($produtos, $repositorios){
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        foreach ($produtos as $produto){
            $reservaEstoqueRepo->adicionaReservaEstoque($produto['idPicking'],$produto['produtos'],"S","E",$produto['idExpedicao'],null,null,null,$produto['idPedido'], $repositorios);
        }
    }


    public function gerarReservaSaidaPulmao ($produtos, $repositorios)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $repositorios['estoqueRepo'];
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];

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

                    if ($qtdRestante > $qtdEstoque) {
                        $qtdSeparar = $qtdEstoque;
                    } else {
                        $qtdSeparar = $qtdRestante;
                    }

                    $qtdRestante = $qtdRestante - $qtdSeparar;
                    $produtosSeparar = $produto['produtos'];
                    foreach ($produtosSeparar as $key=> $produtoSeparar){
                        $produtosSeparar[$key]['qtd'] = ($qtdSeparar * -1);
                    }

                    $reservaEstoqueRepo->adicionaReservaEstoque($idPulmao,$produtosSeparar,"S","E",$idExpedicao,null,null,null,$idPedido, $repositorios);
                }
            }

            $this->getEntityManager()->flush();
        }
    }

}
