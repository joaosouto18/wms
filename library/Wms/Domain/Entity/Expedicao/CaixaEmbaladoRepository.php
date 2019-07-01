<?php


namespace Wms\Domain\Entity\Expedicao;


use Wms\Domain\Configurator;
use Wms\Domain\EntityRepository;

class CaixaEmbaladoRepository extends EntityRepository
{
    /**
     * @param array $params
     * @param bool $flush
     * @return CaixaEmbalado
     * @throws \Exception
     */
    public function save(array $params, $flush = true)
    {
        try {
            /** @var CaixaEmbalado $entity */
            $entity = Configurator::configure(new CaixaEmbalado(), $params);

            $this->_em->persist($entity);
            if ($flush) $this->_em->flush();

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}