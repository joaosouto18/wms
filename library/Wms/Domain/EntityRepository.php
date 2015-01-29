<?php

namespace Wms\Domain;

use Doctrine\ORM\EntityRepository as EntityRepositoryDoctrine;


class EntityRepository extends EntityRepositoryDoctrine
{
    /**
     * Retorna os valores baseados nos campos Id e Descrição
     * 
     * @param array $criteria Criterio da busca
     * @return type 
     */
    public function getIdDescricao(array $criteria = array())
    {
	$array = array();
	foreach ($this->findBy($criteria) as $entity)
	    $array[$entity->getId()] = $entity->getDescricao();
        
	return $array;
    }
    
    /**
     * Retorna os valores baseados nos campos Id e Nome
     * 
     * @param array $criteria
     * @return type 
     */
    public function getIdNome(array $criteria = array())
    {
	$array = array();
	foreach ($this->findBy($criteria) as $entity)
	    $array[$entity->getId()] = $entity->getNome();
        
	return $array;
    }
}
