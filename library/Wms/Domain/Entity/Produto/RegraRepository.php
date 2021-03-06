<?php
namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\Regra as RegraEntity;

/**
 * Regra
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */

class RegraRepository extends EntityRepository
{
    public function save(RegraEntity $regraEntity, array $values)
    {
	extract($values['identificacao']);
	$regraEntity->setDescricao($descricao);
	$this->getEntityManager()->persist($regraEntity);
    }

    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Produto\Regra', $id);
	$count  = $proxy->getTipos()->count();
	if ($count > 0){
	    throw new \Exception("Não é possivel remover a Regra {$proxy->getDescricao()}.Ela está vinculada a um tipo(s) de produto.");
        }
	
	$em->remove($proxy);
    }

}
