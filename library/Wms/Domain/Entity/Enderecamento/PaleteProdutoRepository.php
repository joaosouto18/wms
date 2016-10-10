<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;

class PaleteProdutoRepository extends EntityRepository
{
    public function getQtdTotalEnderecadaByRecebimento($idRecebimento, $codProduto, $grade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(pp.qtd) qtd')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->where("p.recebimento = $idRecebimento 
                 AND pp.codProduto = '$codProduto' 
                 AND pp.grade = '$grade'
                 AND (p.codStatus <> ". Palete::STATUS_EM_RECEBIMENTO . " OR p.impresso = 'S')");

        return $sql->getQuery()->getResult();
    }

    public function getProdutoByUma($uma)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('prod')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->innerJoin('wms:Produto', 'prod', 'WITH', 'prod.id = pp.codProduto AND prod.grade = pp.grade')
            ->where("p.id = $uma");

        return $sql->getQuery()->getResult();

    }


}
