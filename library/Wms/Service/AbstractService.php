<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 01/06/2016
 * Time: 16:18
 */

namespace Wms\Service;


use Doctrine\ORM\EntityManager;
use Wms\Domain\Configurator;

abstract class AbstractService
{

    /**
     * @var EntityManager
     */
    protected $em;
    protected $entity;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function insert(array $data)
    {
        $entity = new $this->entity($data);

        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    public function update(array $data)
    {
        $entity = $this->em->getReference($this->entity, $data['id']);
        Configurator::configure($entity, $data);

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    public function delete($id)
    {
        $entity = $this->em->getReference($this->entity, $id);
        if($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            return $id;
        }
    }

    public function getEntity($id)
    {
        $entity = $this->em->getReference($this->entity, $id);
        return $entity;
    }

}
