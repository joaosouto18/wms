<?php

namespace Core\Plugin;

use \Zend_Controller_Action_HelperBroker as HelperBroker;

/**
 * Description of Auth
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 */
class Auth extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var Zend_Auth
     */
    protected $auth = null;

    /**
     * @var array
     */
    protected $notLoggedRoute = array(
        'controller' => 'auth',
        'action' => 'login',
        'module' => 'web'
    );

    /**
     * @var array
     */
    protected $forbiddenRoute = array(
        'controller' => 'error',
        'action' => 'forbidden',
        'module' => 'web'
    );
    
    /**
     * @var array
     */
    protected $allowedAction = array(
        'mass-delete',
    );


    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
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
            } else if (in_array($action, $this->allowedAction)) {
            // open action
            } elseif(strstr($action, 'json') || strstr($action, 'ajax') || strstr($action, 'pdf')) {
                //esse if garante que paginas com json declarado nao precisam passar por autenticacao
            } elseif (!$this->isAuthorized($controller, $action, $request)) {
                $controller = $this->forbiddenRoute['controller'];
                $action = $this->forbiddenRoute['action'];
                $module = $this->forbiddenRoute['module'];
                $module = $this->notLoggedRoute['module'];
            }
            
        } catch (\Zend_Acl_Role_Registry_Exception $e) {
            //problemas com identity invalida, limpo auth e peco para logar novamente
            \Wms\Service\Auth::logout();
            //refaco a rota
            $controller = $this->notLoggedRoute['controller'];
            $action = $this->notLoggedRoute['action'];
        }

        $request->setControllerName($controller);
        $request->setActionName($action);
        $request->setModuleName($module);
    }

    protected function isAuthorized($controller, $action, \Zend_Controller_Request_Abstract $request)
    {
        $role = $this->usuario->getRoleId();

        $Acl = new \Wms\Configuration\Acl();
        if ($Acl->isDefaultModule($request->getModuleName())) {
            $resource = $controller;
        } else {
            $Acl->setResourceByRequest($request);
            $resource =  $Acl->getResource();
        }

        if (!$this->acl->has($resource) ||
                !$this->acl->isAllowed($role, $resource, $action)) {
            return false;
        }

        return true;
    }



}