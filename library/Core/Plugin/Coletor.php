<?php

namespace Core\Plugin;

use \Zend_Controller_Action_HelperBroker as HelperBroker;

class Coletor extends \Zend_Controller_Plugin_Abstract
{
    /**
     * @var array
     */
    protected $notLoggedRoute = array(
        'controller' => 'auth',
        'action' => 'login',
    );

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request 
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('layout')
                ->setLayoutPath(APPLICATION_PATH . "/modules/mobile/views/layout/");

        // authentication
        $auth = \Zend_Auth::getInstance();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        try {
            if (!$auth->hasIdentity()) {
                $controller = $this->notLoggedRoute['controller'];
                $action = $this->notLoggedRoute['action'];
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
    }

}