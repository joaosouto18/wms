<?php
namespace Wms\Configuration;

class Acl
{

    private $_resource = array();

    public function checkModuleExists($resource){
        $result = preg_split('/:/',$resource);
        if (count($result) == 2) {
            $this->_resource['MODULE']       = $result[0];
            $this->_resource['CONTROLLER']   = $result[1];
            return true;
        } else {
           return false;
        }
    }

    public function isDefaultModule($module) {
        $config         = \Zend_Registry::get('config');
        $defaultModule  = $config->resources->frontController->defaultModule;
        return $module == $defaultModule;
    }

    public function setResourceByRequest(\Zend_Controller_Request_Abstract $request) {
        $this->_resource['MODULE']       = $request->getModuleName();
        $this->_resource['CONTROLLER']   = $request->getControllerName();
    }

    public function getResource() {
        return $this->_resource['MODULE'] . ":" . $this->_resource['CONTROLLER'];
    }

    public function getModule() {
        return $this->_resource['MODULE'];
    }

    public function getController() {
        return $this->_resource['CONTROLLER'];
    }

}