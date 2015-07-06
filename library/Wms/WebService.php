<?php

/**
 * Description of WebService
 *
 * @author Daniel Lima <yourwebmaker@gmail.com>
 */
class Wms_WebService
{
    /**
     * Retrieve the Doctrine Container.
     *
     * @return Bisna\Doctrine\Container
     */
    protected function __getDoctrineContainer()
    {
	return \Zend_Registry::get('doctrine');
    }
    
    /**
     * Retrieve the ServiceLocator Container.
     *
     * @return Bisna\Base\Service\ServiceLocator
     */
    protected function __getServiceLocator()
    {
	return \Zend_Registry::get('serviceLocator');
    }

    public function trimArray(&$array)
    {
        foreach($array as &$value)
            is_array($value) ? $this->trimArray($value):$value=trim($value);
        unset($value);
    }
}