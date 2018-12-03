<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 13:30
 */

namespace Wms\Domain\Entity\InventarioEndProd;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioEndProdRepository extends EntityRepository
{
    /**
     * @return InventarioEnderecoNovo
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}