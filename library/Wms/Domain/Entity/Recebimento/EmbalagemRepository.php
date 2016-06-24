<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class EmbalagemRepository extends EntityRepository
{
    public function getEmbalagemByRecebimento($recebimento, $codProduto, $grade)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('re')
            ->from('wms:Recebimento\Embalagem', 're')
            ->innerJoin('re.embalagem', 'e')
            ->where("re.recebimento = $recebimento")
            ->andWhere("e.codProduto = '$codProduto'")
            ->andWhere("e.grade = '$grade'");

        return $source->getQuery()->getArrayResult();
    }

}
