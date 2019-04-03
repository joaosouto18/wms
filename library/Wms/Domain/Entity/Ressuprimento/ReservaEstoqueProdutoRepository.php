<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\NullOutput;

class ReservaEstoqueProdutoRepository extends EntityRepository
{
    public function getReservaEstoqueProduto($pedido, $codProduto, $dscGrade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('rep')
            ->from('wms:Ressuprimento\ReservaEstoqueExpedicao', 'ree')
            ->innerJoin('ree.reservaEstoque', 're')
            ->innerJoin('wms:Ressuprimento\ReservaEstoqueProduto', 'rep', 'WITH', 're.id = rep.reservaEstoque')
            ->where("ree.pedido = $pedido")
            ->andWhere("rep.codProduto = $codProduto AND rep.grade = $dscGrade");

        return $sql->getQuery()->getSingleScalarResult();

    }
}
