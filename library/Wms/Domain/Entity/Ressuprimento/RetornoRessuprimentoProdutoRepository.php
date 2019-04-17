<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 22/03/2019
 * Time: 14:39
 */

namespace Wms\Domain\Entity\Ressuprimento;




class RetornoRessuprimentoProdutoRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return RetornoRessuprimentoProduto
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var RetornoRessuprimentoProduto $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}