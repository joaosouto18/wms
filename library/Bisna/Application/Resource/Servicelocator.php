<?php

// Zend Framework cannot deal with Resources using namespaces
namespace Bisna\Application\Resource;

/**
 * Zend Application Resource ServiceLocator class
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class Servicelocator extends \Zend_Application_Resource_ResourceAbstract
{
    /**
     * Initializes ServiceLocator Container.
     *
     * @return Bisna\Base\Service\ServiceLocator
     */
    public function init()
    {
        $this->getBootstrap()->bootstrap('doctrine');

        $config = $this->getOptions();

        // Starting Context
        $contextClass = $config['context']['adapterClass'];
        $context = new $contextClass($config['context']['options']);
        
        // Starting ServiceLocator container
        $container = new \Bisna\Base\Service\ServiceLocator($context, \Zend_Registry::get('doctrine'));

        // Add to Zend Registry
        \Zend_Registry::set('serviceLocator', $container);

        return $container;
    }
}