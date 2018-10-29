<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class VolumeRepository extends EntityRepository
{
    public function getVolumeByRecebimento($recebimento, $codProduto, $grade, $lote = null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('rv')
            ->from('wms:Recebimento\Volume', 'rv')
            ->innerJoin('rv.volume', 'v')
            ->where("rv.recebimento = $recebimento")
            ->andWhere("v.codProduto = '$codProduto'")
            ->andWhere("v.grade = '$grade'");
        if($lote != null) {
            $source->andWhere("rv.lote = '$lote'");
        }
        return $source->getQuery()->getArrayResult();
    }
}
