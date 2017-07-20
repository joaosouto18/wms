<?php

namespace Core\Plugin;

use \Zend_Controller_Action_HelperBroker as HelperBroker;

class Produtividade extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var Zend_Auth
     */
    protected $auth = null;

    /**
     * @var array
     */
    protected $notLoggedRoute = array(
        'controller' => 'produtividade',
        'action' => 'run',
        'module' => 'produtividade',
        'param' => 'dia'
    );

    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function preDispatch(\Zend_Controller_Request_Abstract $request) {
        $this->auth = \Zend_Auth::getInstance();
        $this->acl = \Zend_Registry::get('acl');
        $this->usuario = $this->auth->getStorage()->read();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $module = $request->getModuleName();
        try {
            if (!$this->auth->hasIdentity()) {
                $controller = $this->notLoggedRoute['controller'];
                $action = $this->notLoggedRoute['action'];
                $module = $this->notLoggedRoute['module'];
                $param = $this->notLoggedRoute['param'];
               
                if (strpos($_SERVER['REQUEST_URI'], "/$controller/$module/$action/$param/") === false) {
                    \Wms\Service\Auth::logout();
                    $controller = $this->notLoggedRoute['controller'];
                    $action = $this->notLoggedRoute['action'];
                } else {
                    $request->setControllerName($controller);
                    $request->setActionName($action);
                    $request->setModuleName($module);
                }
            }
        } catch (\Zend_Acl_Role_Registry_Exception $e) {
            //problemas com identity invalida, limpo auth e peco para logar novamente
            \Wms\Service\Auth::logout();
            //refaco a rota
            $controller = $this->notLoggedRoute['controller'];
            $action = $this->notLoggedRoute['action'];
        }
    }

}
