<?php

namespace Wms\Module\Web;

/**
 * Description of Report
 *
 * @author medina
 */
class Report
{

    /**
     * Gets the entity manager
     * 
     * @param string $name
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm($name = null)
    {
        return \Zend_Registry::get('doctrine')->getEntityManager($name);
    }

}