<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;

class ReservaEstoqueExpedicaoRepository extends EntityRepository
{

    public function gerarReservaSaidaPicking ($produtos){
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        foreach ($produtos as $produto){
            $reservaEstoqueRepo->adicionaReservaEstoque($produto['idPicking'],$produto['produtos'],"S","E",$produto['idExpedicao']);
        }
    }


    public function gerarReservaSaidaPulmao ($produtos)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        foreach ($produtos as $produto) {
            $idExpedicao = $produto['idExpedicao'];
            $codProduto = $produto['produtos'][0]['codProduto'];
            $grade = $produto['produtos'][0]['grade'];
            $qtdRestante = $produto['produtos'][0]['qtd'];
            $idVolume = $produtos['produto']['0']['codProdutoVolume'];

            $estoquePulmao = $estoqueRepo->getEstoquePulmaoByProduto($codProduto, $grade,$idVolume, false);
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
                        $produtosSeparar[$key]['qtd'] = $qtdSeparar;
                    }

                    $reservaEstoqueRepo->adicionaReservaEstoque($idPulmao,$produtosSeparar,"S","E",$idExpedicao);
                }
            }

            $this->getEntityManager()->flush();
        }
    }

}
