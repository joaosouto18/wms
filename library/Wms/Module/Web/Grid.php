<?php

namespace Wms\Module\Web;

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
     * @return Doctrine\ORM\EntityManager 
     */
    public function getEntityManager($em = null)
    {
        return \Zend_Registry::get('doctrine')->getEntityManager($em);
    }

}
