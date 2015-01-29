<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    Doctrine\Common\Persistence\ObjectRepository;


class ClasseRepository extends EntityRepository
{
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