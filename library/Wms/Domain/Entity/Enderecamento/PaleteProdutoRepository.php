<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;

class PaleteProdutoRepository extends EntityRepository
{
    public function getQtdTtotalEnderecadaByRecebimento($idRecebimento, $codProduto, $grade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(pp.qtd) qtd')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->where("p.recebimento = $idRecebimento AND pp.codProduto = '$codProduto' AND pp.grade = '$grade'");

        return $sql->getQuery()->getResult();
    }
}
