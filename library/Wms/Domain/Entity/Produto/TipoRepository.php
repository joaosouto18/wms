<?php
namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\Tipo as TipoEntity;

/**
 * Tipo
 */
class TipoRepository extends EntityRepository
{
    public function save(TipoEntity $tipoEntity, array $values)
    {
        
	$em = $this->getEntityManager();
	extract($values);
	
	$tipoEntity->getRegras()->clear();
	$em->persist($tipoEntity);
	
	foreach ($regras['regras'] as $key => $codRegra) {
	    $regra = $em->getReference('wms:Produto\Regra', $codRegra);
	    $tipoEntity->getRegras()->add($regra);
	}	
	
	$tipoEntity->setDescricao($identificacao['descricao']);
	
	$em->persist($tipoEntity);
    }

    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Produto\Tipo', $id);
	$em->remove($proxy);
    }

}