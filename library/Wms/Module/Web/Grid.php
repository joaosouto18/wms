<?php

namespace Wms\Module\Web;

use Doctrine\ORM\EntityManager;

/**
 *  Generic
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Grid extends \Core\Grid
{

    /**
     * Return an entityManager. 
     * 
     * @param string $em nome do entitymanager
     * @return EntityManager
     * @throws \Exception
     */
    public function getEntityManager($em = null)
    {
        return \Zend_Registry::get('doctrine')->getEntityManager($em);
    }

}
