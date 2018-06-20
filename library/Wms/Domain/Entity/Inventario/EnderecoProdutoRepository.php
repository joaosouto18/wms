<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Produto;


class EnderecoProdutoRepository extends EntityRepository
{
    /**
     * @param $codProduto
     * @param $grade
     * @param $enderecoEn
     * @param Produto|null $produtoEn
     */
    public function save ($codProduto, $grade, Endereco $enderecoEn, Produto $produtoEn = null)
    {
        if (empty($produtoEn)){
            $produtoEn = $this->_em->getRepository('wms:Produto')->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
        }

        $endProd = new EnderecoProduto();
        $endProd->setCodProduto($codProduto);
        $endProd->setGrade($grade);
        $endProd->setProduto($produtoEn);
        $endProd->setInventarioEndereco($enderecoEn);
        $this->_em->persist($endProd);
    }

}