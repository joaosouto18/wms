<?php

namespace Bisna\Base\Application\Module\Bootstrap;
/**
 * Boostrap class responsible for loading ZF dependencies and initializing
 * resources of a specific module
 *
 * @category Bisna
 * @package Base
 * @subpackage Module
 */
class Bootstrap extends \Zend_Application_Module_Bootstrap
{
    /**
     * {@inheritdoc}
     */
    public function initResourceLoader()
    {
        $loader = $this->getResourceLoader();
        
        // Attaching Action Helper (aka. Controller Helper)
        $loader->removeResourceType('actionhelper');
        $loader->addResourceType('actionhelper', 'controllers/helpers', 'Controller_Helper');
        
        // Fixing wrong Plugin path
        $loader->removeResourceType('plugin');
        $loader->addResourceType('plugin', 'controllers/plugins', 'Controller_Plugin');
        
        // Adding the controller helper path
        \Zend_Controller_Action_HelperBroker::addPath(
            APPLICATION_PATH . '/modules/' . lcfirst($this->getModuleName()) . '/controllers/helpers',
            ucfirst($this->getModuleName()) . '_Controller_Helper'
        );
    }

    /**
     * Initializes module configuration settings.
     * 
     * @return \Zend_Config 
     */
    public function _initModuleConfig()
    {
        $moduleConfigFile = APPLICATION_PATH . '/modules/' . strtolower($this->getModuleName()) . '/configs/module.ini';
        $moduleConfig     = new \Zend_Config_Ini($moduleConfigFile, APPLICATION_ENV);
        $options          = $moduleConfig->toArray();
        
        if ( ! empty($options['includepaths'])) {
            $appBootstrap = $this->getApplication();
            $application  = $appBootstrap->getApplication();

            $application->setIncludePaths($options['includepaths']);
        }
        
        $this->setOptions($options);
        return $moduleConfig;
    }
    
    /**
     * Loads up a logger
     */
    public function _initLogger()
    {
        //\SWAT_Log::loadLogger($this->getModuleName());
    }
    
}