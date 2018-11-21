<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Recebimento\ModeloRecebimento as ModeloRecebimentoEn;

class ModeloRecebimentoRepository extends EntityRepository
{
    public function save(ModeloRecebimentoEn $modeloRecebimentoEntity, array $values)
    {
        $modeloRecebimentoEntity->setDescricao($values['descricao']);
        $modeloRecebimentoEntity->setControleValidade($values['controleValidade']);

        $this->_em->persist($modeloRecebimentoEntity);
        $this->_em->flush();
    }

    public function getModelosRecebimento() {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('mr')
            ->from('wms:Recebimento\ModeloRecebimento', 'mr')
            ->orderBy("mr.id");

        return $source->getQuery()->getArrayResult();
    }

}
