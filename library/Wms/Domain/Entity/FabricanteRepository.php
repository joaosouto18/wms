<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    \Doctrine\Common\Persistence\ObjectRepository;


class FabricanteRepository extends EntityRepository implements ObjectRepository
{
    
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Fabricante', $id);
	$em->remove($proxy);
    }

    
     /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('nome' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getNome();
        }

        return $valores;
    }
}