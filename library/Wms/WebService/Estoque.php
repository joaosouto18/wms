<?php

use Wms\Domain\Entity\Expedicao;

class estoque {
    /** @var string */
    public $codProduto;
    /** @var string */
    public $grade;
    /** @var int */
    public $estoqueArmazenado;
    /** @var int */
    public $estoqueDisponivel;
}

class filtroProduto{
    /** @var string */
    public $codProduto;
    /** @var string */
    public $grade;
}

class Wms_WebService_Estoque extends Wms_WebService
{

    private $_em;

    public function __construct()
    {
        $this->_em = $this->__getDoctrineContainer()->getEntityManager();
    }

    /**
     *  Retorna o estoque armazenado e disponível de todos os produtos cadastrados
     *
     * @return estoque[] Estoque Armazenado e disponível dos produtos
     */
    public function consultaEstoqueGeral(){

        $produtos = array();

        $estoqueRepo = $this->_em->getRepository("wms:Enderecamento\Estoque");
        $estoques = $estoqueRepo->getEstoqueByProduto();

        foreach ($estoques as $estoque) {
            $produto = new estoque();
            $produto->codProduto = $estoque['COD_PRODUTO'];
            $produto->grade = $estoque['DSC_GRADE'];
            $produto->estoqueArmazenado = $estoque['QTD_ESTOQUE_TOTAL'];
            $produto->estoqueDisponivel = $estoque['QTD_ESTOQUE_DISPONIVEL'];
            $produtos[] = $produto;
        }

        return $produtos;
    }

    /**
     *  Retorna o estoque armazenado e disponível de todos os produtos informados no filtro
     *     *
     * @param filtroProduto[] $produtos filtro com os produtos que deseja consultar estoque
     * @return estoque[] Estoque Armazenado e disponível dos produtos
     */
    public function consultarEstoque ($produtos) {

        $implodeProd = array();
        foreach ($produtos as $prod) {
            $implodeProd[] = $prod->codProduto;
        }
        $produtosParam = implode(",", $implodeProd);

        $produtosResult = array();

        $estoqueRepo = $this->_em->getRepository("wms:Enderecamento\Estoque");
        $estoques = $estoqueRepo->getEstoqueByProduto($produtosParam);

        foreach ($estoques as $estoque) {
            $produto = new estoque();
            $produto->codProduto = $estoque['COD_PRODUTO'];
            $produto->grade = $estoque['DSC_GRADE'];
            $produto->estoqueArmazenado = $estoque['QTD_ESTOQUE_TOTAL'];
            $produto->estoqueDisponivel = $estoque['QTD_ESTOQUE_DISPONIVEL'];
            $produtosResult[] = $produto;
        }

        return $produtosResult;
   }

}