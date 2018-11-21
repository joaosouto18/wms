<?php

namespace Core\Controller;

/**
 * Description of PluginAbstract
 *
 * @author daniel
 */
class PluginAbstract extends \Zend_Controller_Plugin_Abstract {

    protected $rotasProibidas = array();
    protected $modulosProibidos = array();
    protected $controllersProibidos = array();

    /**
     * 
     */
    public function verificaRotas(\Zend_Controller_Request_Abstract $request) {
        // request
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();
        $moduleName = $request->getModuleName();

        foreach ($this->rotasProibidas as $rota) {
            // caso modulo esteja na lista de proibidos
            if (in_array($moduleName, $this->modulosProibidos))
                return false;

            // caso dados nao validos
            if ($rota['controller'] == $controllerName && $rota['action'] == $actionName && $rota['module'] == $moduleName)
                return false;
        }

        return true;
    }

}
