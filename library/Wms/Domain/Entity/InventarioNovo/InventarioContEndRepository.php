<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 27/11/2018
 * Time: 09:15
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndRepository extends EntityRepository
{
    /**
     * @return InventarioContEnd
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