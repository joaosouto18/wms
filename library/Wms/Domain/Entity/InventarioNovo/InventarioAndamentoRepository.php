<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 18/12/2018
 * Time: 10:37
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;
use Wms\Domain\EntityRepository;

class InventarioAndamentoRepository extends EntityRepository
{

    /**
     * @return InventarioAndamento
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