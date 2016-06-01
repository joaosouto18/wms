<?php

namespace Wms\Service;

use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Importacao\Arquivo;

class ImportacaoArquivo extends AbstractService
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        $this->entity = 'wms:Importacao\Arquivo';
    }

    public function alterarStatus($id)
    {
        /** @var Arquivo $entity */
        $entity = $this->getEntity($id);
        if ($entity->getAtivo() == Arquivo::STS_ATIVO){
            $entity->setAtivo(Arquivo::STS_INATIVO);
        } else {
            $entity->setAtivo(Arquivo::STS_ATIVO);
        }
        self::update($entity->toArray());
    }
}