<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 01/06/2016
 * Time: 16:18
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Wms\Domain\Configurator;

abstract class AbstractService extends EntityService
{

    /**
     * @var EntityManager $em
     */
    protected $em;
    protected $entityName;

    protected function initializeService()
    {
        $this->em = $this->getEntityManager();
        $this->entityName = $this->options['entityClassName'];
    }

    /**
     * @param object|array $data
     * @param boolean $executeFlush
     * @return null | object
     * @throws \Exception
     */
    public function save($data, $executeFlush = true)
    {
        try {
            $entity = null;
            if (is_array($data)) {
                $entity = new $this->entityName();
                Configurator::configure($entity, $data);
            } else if (is_object($data)){
                $entity = $data;
            }

            $this->em->persist($entity);
            if ($executeFlush) $this->em->flush();

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     * @param bool $executeFlush
     * @return object
     * @throws \Exception
     */
    public function update(array $data, $executeFlush = true)
    {
        try {
            $entity = $this->find($data['id']);
            if (empty($entity))
                throw new \Exception("Nenhum registro para '$this->entityName' foi encontrado pelo ID '$data[id]'", 404);

            Configurator::configure($entity, $data);

            $this->em->persist($entity);
            if ($executeFlush) $this->em->flush();

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id string|int
     * @return object
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference($id)
    {
        $entity = $this->em->getReference($this->entityName, $id);
        return $entity;
    }

    /**
     * @return object[]
     */
    public function findAll()
    {
        return $this->em->getRepository($this->entityName)->findAll();
    }
}
