<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class VolumeRepository extends EntityRepository
{
    public function getVolumeByRecebimento($recebimento, $codigoBarras)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('rv')
            ->from('wms:Recebimento\Volume', 'rv')
            ->innerJoin('rv.volume', 'v')
            ->where("rv.recebimento = $recebimento")
            ->andWhere("v.codigoBarras = '$codigoBarras'");

        return $source->getQuery()->getArrayResult();
    }

}
