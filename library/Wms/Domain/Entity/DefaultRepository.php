<?php
namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository;


class DefaultRepository extends EntityRepository implements \Doctrine\Common\Persistence\ObjectRepository
{   
    /**
     * Persist uma entidade
     * @param Acao $acao
     * @param array $values valores vindo de um formulário
     */
    public function save($entity)
    {
	$this->getEntityManager()->persist($entity);
    }
}
