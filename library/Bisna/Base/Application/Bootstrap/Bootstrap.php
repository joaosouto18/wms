<?php

namespace Bisna\Base\Application\Bootstrap;

/**
 * Boostrap class responsible for loading ZF dependecies and initializing
 * resources
 *
 * @category MKX
 * @package Base
 * @subpackage Application
 */
class Bootstrap extends \Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Initializes configuration settings.
     * 
     * @return \Zend_Config 
     */
    public function _initConfig()
    {
        $config = new \Zend_Config($this->getApplication()->getOptions(), true);

        // Required: Boostrap requires this Registry entry
        \Zend_Registry::set('config', $config);
	
        return $config;
    }
    
    /**
     * Merge global and module specific resource configurations.
     * 
     * @param string $configFileName
     * @return \Zend_Config_Ini 
     */
    protected function mergeResourceConfiguration($configFileName)
    {
        $defaultConfigFilePath = '/configs/' . $configFileName . '.ini';
        $localConfigFilePath   = '/configs/' . $configFileName . '.' . APPLICATION_EXEC . '.ini';
        
        $applicationConfig     = $this->getZendConfigInstance(APPLICATION_PATH . $defaultConfigFilePath);
        
        if (is_readable($localConfigFilePath)) {
            $applicationConfig->merge($this->getZendConfigInstance(APPLICATION_PATH . $localConfigFilePath));
        }
        
        $directoryIterator = new \DirectoryIterator(APPLICATION_PATH . '/modules');
        
        foreach ($directoryIterator as $moduleDirectory) {
            if ( ! $this->isModuleDirectory($moduleDirectory)) continue;
            
            $this->mergeModuleResourceConfiguration(
                $applicationConfig, 
                $moduleDirectory->getPathname() . $defaultConfigFilePath, 
                $moduleDirectory->getPathname() . $localConfigFilePath
            );
        }
        
        return $applicationConfig;
    }
    
    /**
     * Merge module resource configurations.
     * 
     * @param Zend_Config $applicationConfig
     * @param string $defaultModuleConfigFilePath
     * @param string $localModuleConfigFilePath 
     */
    private function mergeModuleResourceConfiguration(
        \Zend_Config $applicationConfig, $defaultModuleConfigFilePath, $localModuleConfigFilePath
    ) {
        if (is_readable($defaultModuleConfigFilePath)) {
            $applicationConfig->merge($this->getZendConfigInstance($defaultModuleConfigFilePath));
        }

        if (is_readable($localModuleConfigFilePath)) {
            $applicationConfig->merge($this->getZendConfigInstance($localModuleConfigFilePath));
        }
    }
    
    /**
     * Check if a given value is a valid module directory
     * 
     * @param mixed $moduleDirectory
     * @return boolean 
     */
    private function isModuleDirectory($moduleDirectory)
    {
        return ! $moduleDirectory->isDot() && $moduleDirectory->isDir() 
            && ! in_array($moduleDirectory->getFilename(), array('.svn', '.cvs'));
    }
    
    /**
     * Retrieve a modifiable instance of Zend_Config
     * 
     * @param string $configFilePath
     * @return \Zend_Config_Ini 
     */
    private function getZendConfigInstance($configFilePath)
    {
        return new \Zend_Config_Ini($configFilePath, APPLICATION_ENV, array('allowModifications' => true));
    }
}
