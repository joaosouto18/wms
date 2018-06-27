<?php

namespace Bisna\Base\Domain\Entity;

use Doctrine\ORM\Query\Expr,
    Doctrine\ORM\Mapping\MappingException,
    Bisna\Base\Service as BaseService;

/**
 * Abstract Service, responsible for the contract that needs to be filled by
 * every Service on the platform, defines the methods that need to be available
 *
 * @category Bisna
 * @package Base
 * @subpackage Entity
 */
class EntityService extends BaseService\Service
{
    /**
     * Create a new entity filter criteria.
     * 
     * @param string $alias Optional root alias (default = "e")
     * 
     * @return Filter\Criteria 
     */
    public function buildFilterCriteria($alias = 'e')
    {
        return new Filter\Criteria(
            $this->getRepository($this->options['entityManagerRead'])->createQueryBuilder($alias)
        );
    }
    
    /**
     * Retrieve the associated Entity ClassMetadta.
     * 
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        $emName = $this->options['entityManagerRead'];
        
        return $this->getEntityManager($emName)->getClassMetadata($this->options['entityClassName']);
    }
    
    /**
     * Returns a list of filtered entities
     *
     * @param Filter\Criteria $criteria
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function filter(Filter\Criteria $criteria = null)
    {
        try {
            if ($criteria === null) {
                $criteria = $this->buildFilterCriteria();
            }
            
            return $this->getRepository($this->options['entityManagerRead'])->filter($criteria);
        } catch (\Exception $e) {
            $this->logException($e);
            
            throw new \Exception('Unable to retrieve entities.', 500, $e);
        }
    }

    /**
     * Executes a named query to return one or more results
     * 
     * @param string $queryName
     * @param array $parameters
     * @param int $limit
     * 
     * @return mixed 
     */
    public function filterNamed($queryName, $parameters = array(), $limit = null)
    {
        try {
            return $this->getRepository()->filterByNamed($queryName, $parameters, $limit);
        } catch(MappingException $e) {
            $this->logException($e);
            throw new BaseService\Exception('Unknown named query "'.$queryName.'"', 500, $e);
        
        } catch (\Exception $e) {
            $this->logException($e);
            throw new BaseService\Exception('Unable to retrieve entities.', 500, $e);
        }
    }

    /**
     * Retrieve the object by its identifier
     *
     * @param integer|string $id
     * @return Bisna\Base\Domain\Entity\Entity
     */
    public function get($id)
    {
	return $this->getRepository($this->options['entityManagerRead'])->find($id);   
    }

    public function findBy(array $criteria)
    {
        return $this->getRepository($this->options['entityManagerRead'])->findBy($criteria);
    }
    
    public function findOneBy(array $criteria)
    {
	return $this->getRepository($this->options['entityManagerRead'])->findOneBy($criteria);
    }

    /**
     * Delete an entity by its identifier
     *
     * @param integer|string $id
     * @return boolean
     */
    public function delete($id)
    {
        $em = $this->getEntityManager($this->options['entityManagerReadWrite']);
        
        try {
            $em->beginTransaction();
            
            $this->getRepository($this->options['entityManagerReadWrite'])->delete($id);
            
            $em->flush();
            $em->commit();
            
            return true;
        } catch (\Exception $e) {
            $em->rollback();
            
            $this->logException($e);
            
            throw new BaseService\Exception('Unable to delete entity with ID: ' . $id, 500, $e);
        }
    }

    /**
     * Creates a new resource
     *
     * @param Bisna\Base\Domain\Entity\Entity $entity
     * @return boolean
     */
    public function post($entity)
    {
        /*if ($entity->getId() !== null) {
            throw new \Exception('Entity ID should not be set.', 400);
        }*/

        return $this->save($entity);
    }

    /**
     * Create a new resource or update and existing one
     *
     * @param Bisna\Base\Domain\Entity\Entity $entity
     * @return boolean
     */
    public function put($entity)
    {
        if ($entity->getId() === null) {
            throw new \Exception('Entity ID must be set.', 400);
        }

        return $this->save($entity);
    }

    /**
     * Save entity. Common behaviour in post and put.
     *
     * @param Bisna\Base\Domain\Entity\Entity $entity
     * @return boolean
     */
    protected function save($entity)
    {
        $em = $this->getEntityManager($this->options['entityManagerReadWrite']);
        
        //try {
            $em->beginTransaction();
            
            $this->getRepository($this->options['entityManagerReadWrite'])->save($entity);
            
            $em->flush();
            $em->commit();

            return true;
        /*} catch (\Exception $e) {
            $em->rollback();
            
            //$this->logException($e);
           
            $errorMessage = ($entity->getId() === null)
                ? 'Unable to save new entity.'
                : 'Unable to save entity with ID: ' . $entity->getId();
            
            throw new \Exception($errorMessage, 500, $e);
        }*/
    }

    /**
     * Retrieve the associated repository to this Service's entity.
     *
     * @param string $emName
     *
     * @return Bisna\Base\Domain\Entity\EntityRepository
     */
    protected function getRepository($emName = null)
    {
        return $this->getEntityManager($emName)->getRepository($this->options['entityClassName']);
    }

    /**
     *  Log exception.
     *
     * @param \Exception $exception
     */
    protected function logException($exception)
    {
        /*$loggerService = $this->getService('Logger');
        $loggerService->logException($exception);*/
    }
}