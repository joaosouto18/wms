<?php
namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class VQtdRecebimentoRepository extends EntityRepository
{
    public function getQtdByRecebimento($idRecebimento,$idProduto,$grade, $lote = null)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(v.qtd) qtd')
            ->from('wms:Recebimento\VQtdRecebimento','v')
            ->where("v.codRecebimento = $idRecebimento")
            ->andWhere("v.codProduto = '$idProduto'")
            ->andWhere("v.grade = '$grade'");
        if($lote != null) {
            $sql->andWhere("v.lote = '$lote'");
        }

        return $sql->getQuery()->getArrayResult();
    }
}