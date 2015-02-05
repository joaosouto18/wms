<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class ModeloSeparacaoRepository extends EntityRepository
{

    public function getModelos() {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('m.id , m.descricao')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->orderBy("m.id");

        return $source->getQuery()->getArrayResult();
    }

}