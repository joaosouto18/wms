<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class EmbalagemRepository extends EntityRepository
{
    public function getEmbalagemByRecebimento($recebimento, $codigoBarras)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('re')
            ->from('wms:Recebimento\Embalagem', 're')
            ->innerJoin('re.embalagem', 'e')
            ->where("re.recebimento = $recebimento")
            ->andWhere("e.codigoBarras = '$codigoBarras'");

        return $source->getQuery()->getArrayResult();
    }

}
