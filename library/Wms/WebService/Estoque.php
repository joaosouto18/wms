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

        $produto1 = new estoque();
        $produto1->codProduto = "1010";
        $produto1->grade = "UNICA";
        $produto1->estoqueArmazenado = 10;
        $produto1->estoqueDisponivel = 7;
        $produtos[] = $produto1;

        $produto2 = new estoque();
        $produto2->codProduto = "2020";
        $produto2->grade = "UNICA";
        $produto2->estoqueArmazenado = 8;
        $produto2->estoqueDisponivel = 5;
        $produtos[] = $produto2;

        $produto3 = new estoque();
        $produto3->codProduto = "1015";
        $produto3->grade = "UNICA";
        $produto3->estoqueArmazenado = 14;
        $produto3->estoqueDisponivel = 14;
        $produtos[] = $produto3;

        return $produtos;
    }

    /**
     *  Retorna o estoque armazenado e disponível de todos os produtos informados no filtro
     *     *
     * @param filtroProduto[] $produtos filtro com os produtos que deseja consultar estoque
     * @return estoque[] Estoque Armazenado e disponível dos produtos
     */
    public function consultarEstoque ($produtos) {
        $produtos = array();

        $produto1 = new estoque();
        $produto1->codProduto = "1010";
        $produto1->grade = "UNICA";
        $produto1->estoqueArmazenado = 10;
        $produto1->estoqueDisponivel = 7;
        $produtos[] = $produto1;

        return $produtos;
   }

}